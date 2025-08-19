<?php

declare(strict_types=1);

namespace App\Livewire\Patient\Records;

use App\Classes\eHealth\Api\PersonApi;
use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Core\Arr;
use App\Livewire\Patient\Forms\Api\PatientRequestApi;
use App\Livewire\Patient\Records\Forms\PatientForm as Form;
use App\Models\Person\Person;
use App\Repositories\PersonRepository;
use App\Traits\FormTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

class PatientData extends BasePatientComponent
{
    use FormTrait;

    public Form $form;

    public string $firstName;

    public string $lastName;

    public array $phones = [];

    public array $confidantPersonRelationships;

    /**
     * List of patient authentication methods.
     * @var array
     */
    public array $authenticationMethods;

    /**
     * ID that returns after createAuthMethod request, need for resendSMS request.
     * @var string
     */
    protected string $authMethodId;

    /**
     * ID that returns after createAuthMethod request, need for resendSMS request.
     * @var string
     */
    protected string $authMethodRequestId;

    protected function initializeComponent(): void
    {
        $patient = Person::with('phones')
            ->where('id', $this->patientId)
            ->first()
            ?->toArray();

        $this->firstName = $patient['first_name'];
        $this->lastName = $patient['last_name'];
        $this->phones = $patient['phones'] ?? [];
    }

    public function render(): View
    {
        return view('livewire.patient.records.patient-data');
    }

    /**
     * Get patient verification status.
     *
     * @return void
     */
    public function getVerificationStatus(): void
    {
        try {
            $personVerificationDetails = PersonApi::getPersonVerificationDetails($this->uuid);
            PersonRepository::updateVerificationStatusById(
                $this->uuid,
                $personVerificationDetails['verification_status']
            );

            $this->verificationStatus = $personVerificationDetails['verification_status'];
        } catch (ApiException) {
            $this->dispatch('flashMessage', [
                'message' => __('Не вдалося отримати верифікаційний статус. Спробуйте пізніше.'),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Get patient confidant persons.
     *
     * @return void
     */
    public function getConfidantPersons(): void
    {
        try {
            $buildConfidantRelationshipRequest = PatientRequestApi::buildGetConfidantPersonRelationships(false);
            $confidantPersonRelationships = PersonApi::getConfidantPersonRelationships(
                $this->uuid,
                $buildConfidantRelationshipRequest
            );

            $this->confidantPersonRelationships = $confidantPersonRelationships;
        } catch (ApiException) {
            $this->dispatch('flashMessage', [
                'message' => __('Не вдалося отримати законного представника. Спробуйте пізніше.'),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Get patient authentication methods.
     *
     * @return void
     */
    public function getAuthenticationMethods(): void
    {
        try {
            $response = EHealth::person()->getAuthMethods($this->uuid);

            $this->authenticationMethods = $response->getData();
        } catch (ConnectionException) {
            $this->dispatch('flashMessage', [
                'message' => __('Не вдалося отримати методи автентифікації. Спробуйте пізніше.'),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Deactivate authentication method.
     *
     * @param  array  $data
     * @return void
     */
    public function deactivateAuthMethod(array $data): void
    {
        $this->form->action = 'DEACTIVATE';
        $this->form->authenticationMethod = $data;

        try {
            $validated = $this->form->validate($this->form->rulesForDeactivate());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::person()->createAuthMethod($this->uuid, Arr::toSnakeCase($validated));

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while deactivating auth method request');
                $this->flashGeneralError();

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while deactivating auth method request');
            $this->flashGeneralError();

            return;
        }
    }

    /**
     * Create an authentication method request.
     *
     * @param  array  $data
     * @return void
     */
    public function createAuthMethod(array $data): void
    {
        $this->form->action = 'INSERT';
        $this->form->authenticationMethod = $data;

        try {
            $validated = $this->form->validate($this->form->rules());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::person()->createAuthMethod($this->uuid, Arr::toSnakeCase(removeEmptyKeys($validated)));

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while creating auth method request');
                $this->flashGeneralError();

                return;
            }

            if ($response->getStatusCode() === 200) {
                $this->authMethodId = $response->getData()['id'];
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while creating auth method request');
            $this->flashGeneralError();

            return;
        }
    }

    /**
     * Re-send SMS.
     *
     * @return void
     */
    public function resendSms(): void
    {
        try {
            $response = EHealth::person()->resendAuthOtp($this->uuid, $this->authMethodId);

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while resending auth OTP on declaration request');
                $this->flashGeneralError();

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while resending sms to person');
            $this->flashGeneralError();

            return;
        }

        if ($response->getData()['status'] === 'new') {
            $this->dispatch('flashMessage', [
                'message' => __('SMS успішно надіслано!'),
                'type' => 'success'
            ]);
        }
    }
}
