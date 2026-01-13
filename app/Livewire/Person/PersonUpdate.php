<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Person\AuthenticationMethod;
use App\Enums\Person\AuthenticationMethodAction;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use App\Repositories\Repository;
use App\Rules\PhoneNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Throwable;

/**
 * Used for updating person by using person request call
 */
class PersonUpdate extends PersonComponent
{
    #[Locked]
    public string $uuid;

    public array $authenticationMethods;

    public bool $showAuthMethodModal = false;

    public int $authStep = 0;

    /**
     * Current phone number.
     *
     * @var string|null
     */
    public ?string $phoneNumber = null;

    /**
     * Confirmation code that need for 'Complete OTP Verification' endpoint
     *
     * @var int
     */
    public int $code;

    /**
     * Phone number that person will be used instead of old one.
     *
     * @var string
     */
    public string $newPhoneNumber;

    /**
     * Code for approving phone number.
     *
     * @var int
     */
    public int $verificationCode;

    /**
     * ID that needed for approving auth method.
     *
     * @var string
     */
    #[Locked]
    public string $requestId;

    public function mount(LegalEntity $legalEntity, Person $person): void
    {
        $this->personId = $person->id;
        $this->uuid = $person->uuid;
        $this->baseMount();

        $this->form->person = Arr::toCamelCase(
            $person->load([
                'addresses',
                'documents',
                'phones',
                'authenticationMethods',
                'confidantPerson.person:id,uuid,last_name,first_name,second_name,tax_id,unzr'
            ])->toArray()
        );

        $this->address = Arr::get($this->form->person, 'addresses.0', []);

        if (empty($this->form->person['phones'])) {
            $this->form->person['phones'] = [['type' => null, 'number' => null]];
        }

        if (empty($this->form->person['emergencyContact'])) {
            $this->form->person['emergencyContact']['phones'] = [['type' => null, 'number' => null]];
        }

        $authenticationMethods = $person->authenticationMethods->toArray();

        if ($person->confidantPerson) {
            $this->selectedConfidantPersonId = $person->confidantPerson->person->uuid;
            $confidantPersonData = $person->confidantPerson->person;

            $modifiedMethods = collect($authenticationMethods)->map(
                function (array $method) use ($confidantPersonData) {
                    if ($method['type'] === AuthenticationMethod::THIRD_PERSON->value) {
                        $method['confidantPerson'] = [
                            'name' => $confidantPersonData->fullName,
                            'taxId' => $confidantPersonData->taxId,
                            'unzr' => $confidantPersonData->unzr,
                            'documentsPerson' => $confidantPersonData->documents->toArray()
                        ];
                    }

                    return $method;
                }
            );

            $this->authenticationMethods = $modifiedMethods->toArray();
        } else {
            $this->authenticationMethods = $authenticationMethods;
            $this->phoneNumber = collect($authenticationMethods)
                ->where('type', AuthenticationMethod::OTP->value)
                ->pluck('phoneNumber')
                ->first();
        }
    }

    /**
     * Steps for interaction with auth methods.
     *
     * @param  int  $step
     * @return void
     */
    public function setStep(int $step): void
    {
        $this->authStep = $step;
    }

    /**
     * Show modal for choosing authorize with param.
     *
     * @return void
     */
    public function openAuthMethodModal(): void
    {
        $this->showAuthMethodModal = true;
        $this->authStep = 0;

        try {
            $response = EHealth::person()->getAuthMethods($this->uuid);
            $newAuthMethods = $response->validate();
            $person = Person::whereUuid($this->uuid)->firstOrFail();
            $incomingTypes = collect($newAuthMethods)->pluck('type')->filter()->values();

            // Delete unrelated
            $person->authenticationMethods()
                ->whereNotIn('type', $incomingTypes)
                ->delete();

            // Update or create actual by type
            foreach ($newAuthMethods as $method) {
                $person->authenticationMethods()->updateOrCreate(
                    ['type' => $method['type']],
                    $method
                );
            }

            $this->authenticationMethods = Arr::toCamelCase($response->getData());
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when getting auth methods');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting auth methods');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }
    }

    /**
     * Verify is current phone number belongs to person.
     *
     * @return void
     */
    public function verifyOwnership(): void
    {
        $validated = Validator::make(
            ['phoneNumber' => $this->form->phoneNumber],
            ['phoneNumber' => 'required', new PhoneNumber()]
        )->validate();

        try {
            $response = EHealth::verification()->findByPhoneNumber($validated['phoneNumber']);

            // If phone number is found, it means that phone number is verified, so we move to step with changing number
            if ($response->validate()['phone_number'] === $validated['phoneNumber']) {
                $this->changePhoneNumber($response->validate()['phone_number']);
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when finding for OTP verification');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            if ($exception->getCode() === 404) {
                try {
                    EHealth::verification()->initialize(Arr::toSnakeCase($validated));
                    $this->authStep = 2;
                } catch (ConnectionException $exception) {
                    $this->logConnectionError($exception, 'Error when initialize OTP verification request');
                    Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

                    return;
                } catch (EHealthValidationException|EHealthResponseException $exception) {
                    $this->logEHealthException($exception, 'Error when initialize OTP verification request');

                    if ($exception instanceof EHealthValidationException) {
                        Session::flash('error', $exception->getFormattedMessage());
                    } else {
                        Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
                    }

                    return;
                }
            }
        }
    }

    /**
     * Complete OTP verification.
     *
     * @return void
     */
    public function completeVerifyingOwnership(): void
    {
        $validated = Validator::make(['code' => $this->code], ['code' => 'required', 'integer'])->validate();

        try {
            EHealth::verification()->complete($this->form->phoneNumber, $validated);
            $this->authStep = 4;
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when complete OTP verification request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when complete OTP verification request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }
    }

    /**
     * Update phone number with verified new number.
     *
     * @return void
     */
    public function updatePhoneNumber(): void
    {
        $this->changePhoneNumber($this->newPhoneNumber);
    }

    /**
     * Approve phone number with verification code.
     *
     * @return void
     */
    public function approveUpdatingPhoneNumber(): void
    {
        $validated = Validator::make(
            ['verificationCode' => $this->verificationCode],
            ['verificationCode' => 'required', 'digits:4']
        )->validate();

        try {
            $response = EHealth::person()->approveAuthMethod(
                $this->form->person['uuid'],
                $this->requestId,
                Arr::toSnakeCase($validated)
            );

            // Update uuid with approved
            Person::whereUuid($this->form->person['uuid'])->firstOrFail()
                ->authenticationMethods()
                ->whereType(AuthenticationMethod::OTP)
                ->update(['uuid' => $response->validate()['id']]);

            $this->showAuthMethodModal = false;
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when approving auth method request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when approving auth method request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }
    }

    /**
     * Resend code to phone number.
     *
     * @return void
     */
    public function resendCode(): void
    {
        try {
            EHealth::person()->resendAuthOtp($this->form->person['uuid'], $this->requestId);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when updating auth method request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when updating auth method request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }
    }

    /**
     * Update data for created person.
     *
     * @return void
     */
    public function update(): void
    {
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на оновлення пацієнта.');

            return;
        }

        $this->form->person['addresses'] = [$this->address]; // must be multiple

        try {
            $addressErrors = $this->addressValidation();
            if (!empty($addressErrors)) {
                throw ValidationException::withMessages($addressErrors);
            }

            $validated = $this->form->validate($this->form->rulesForUpdate());
            $this->formKey++;
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->formKey++;

            return;
        }

        $formatted = $this->form->formatForPersonCreationApi(
            array_merge($validated, ['addresses' => $this->form->addresses])
        );
        $formatted['person']['id'] = $this->uuid;

        try {
            // update
            $response = EHealth::personRequest()->create($formatted);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when updating person request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when updating a person request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        if ($response->successful()) {
            // save in DB
            try {
                Repository::personRequest()->update(removeEmptyKeys($response->map($response->validate())));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Failed to update person request');
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }

            $this->form->person['id'] = $response->getData()['id'];
            $this->uploadedDocuments = $response->getUrgent()['documents'];
            $this->viewState = 'new';
        }
    }

    /**
     * Change phone number with new one.
     *
     * @param  string  $phoneNumber
     * @return void
     */
    protected function changePhoneNumber(string $phoneNumber): void
    {
        $validated = Validator::make(
            ['newPhoneNumber' => $phoneNumber],
            ['newPhoneNumber' => 'required', new PhoneNumber()]
        )->validate();

        $dataForApi = [
            'action' => AuthenticationMethodAction::INSERT->value,
            'authentication_method' => [
                'type' => 'OTP',
                'phone_number' => $validated['newPhoneNumber']
            ]
        ];

        try {
            $response = EHealth::person()->createAuthMethod($this->form->person['uuid'], $dataForApi);
            $this->requestId = $response->validate()['id'];
            $this->authStep = 5;
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when creating auth method request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating auth method request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.person.person-edit');
    }
}
