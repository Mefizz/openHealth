<?php

namespace App\Livewire\EmployeeRequest;

use App\Actions\EmployeeRequest\CreateEmployeeRequestAction;
use App\Actions\EmployeeRequest\SubmitToEHealthAction;
use App\Livewire\EmployeeRequest\Forms\EmployeeRequestForm;
use App\Services\Cipher\CipherService; // Ensure this is the correct namespace
use App\Services\Cipher\DTO\CipherPayload;
use App\Traits\FormTrait;
use App\Traits\UsesDictionaries;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\ValidationException; // Add this for type hinting

class EmployeeRequestCreate extends Component
{
    use WithFileUploads, FormTrait {
        getDictionary as traitGetDictionary;
    }

    public EmployeeRequestForm $form;

    public ?string $knedp = '';
    public $keyContainer; // This will hold the UploadedFile instance
    public string $password = '';
    public bool $showSignModal = false;

    public array $certificateAuthorities = [];
    public array $employeeTypePosition = [];

    protected array $rules = [
        'knedp' => 'required',
        'keyContainer' => 'required|file|mimes:p7s,cer,pem,p12,jks,zip,dat', // Added common KEP file extensions
        'password' => 'required',
    ];

    protected array $messages = [
        'knedp.required' => 'Оберіть центр сертифікації',
        'keyContainer.required' => 'Завантажте ключ-контейнер',
        'keyContainer.file' => 'Завантажений файл не є валідним файлом.',
        'keyContainer.mimes' => 'Непідтримуваний формат файлу ключа. Дозволені формати: p7s, cer, pem, p12, jks, zip, dat.',
        'password.required' => 'Введіть пароль',
    ];

    public function mount(CipherService $cipherService)
    {
        $this->form->party['phones'] = [['type' => '', 'number' => '']]; // Ensure at least one phone field is present
        $this->certificateAuthorities = $cipherService->getCertificateAuthorities();

    }

//    protected function getDictionary(): void
//    {
//        $this->traitGetDictionary();
//
//        $this->dictionaries['EMPLOYEE_TYPE'] = $this->getDictionariesFields(
//            config('ehealth.legal_entity_type.' . auth()->user()->legalEntity->type .'.roles'),
//            'EMPLOYEE_TYPE'
//        );
//
//        // Employee can have only those positions which are allowed for his type/role
//        foreach ($this->dictionaries['EMPLOYEE_TYPE'] as $employeeType => $description) {
//            $keys = config("ehealth.employee_type.{$employeeType}.position", []);
//            $this->employeeTypePosition[$employeeType] = $this->getDictionariesFields($keys, 'POSITION');
//        }
//    }

    public function saveDraft()
    {
        try {
            $this->form->validate(); // Validate all form data
            $employeeRequest = app(CreateEmployeeRequestAction::class)->execute($this->form->validatedSnake());

            // You might want to store the knedp, keyContainer, password with the draft
            // For now, let's assume these are only needed for submission to E-Health
            // If you need to persist them, you'd add fields to EmployeeRequest model

            session()->flash('success', __('forms.request_saved_as_draft'));
        } catch (ValidationException $e) {
            $this->dispatch('validation-error'); // Dispatch an event to show validation errors on the front-end
            $this->addError('general', __('forms.validation_failed'));
        } catch (\Exception $e) {
            report($e);
            session()->flash('error', __('forms.save_failed'));
        }
    }

    public function prepareForSigning(): void
    {
        // Validate core party data before showing the signing modal
        $this->form->validate([
            'party.employeeType' => 'required',
            'party.firstName' => 'required',
            'party.lastName' => 'required',
            'party.birthDate' => 'required|date',
            'party.gender' => 'required',
            'party.taxId' => 'required_without:party.documents|nullable|string|min:8|max:10', // Added taxId validation
            'party.documents' => 'required_without:party.taxId', // Added documents validation
            // Add more essential validations for the minimum data required for E-Health submission
        ]);

        // Validate the KEP fields (knedp, keyContainer, password)
        $this->validate();

        $this->showSignModal = true;
    }


    public function submitForApproval(CipherService $cipher)
    {
        $this->validate(); // Валідація knedp, keyContainer, password

        try {
            // Ensure all form data is valid before building the payload
            $this->form->validate();

            $payload = new CipherPayload(
                data: $this->form->validatedSnake(), // Use validated and snake_cased data
                knedp: $this->knedp,
                keyFile: $this->keyContainer,
                password: $this->password
            );

            // Assuming signAndSend returns an array directly from CipherApi
            $signedData = $cipher->signAndSend(
                payload: $payload,
                taxId: $this->form->party['taxId'] // Pass taxId from the form
            );

            // Submit to E-Health using the dedicated action
            $result = app(SubmitToEHealthAction::class)->execute($signedData);

            if ($result) {
                // If submission is successful, update the EmployeeRequest status in DB if it was a draft
                if (isset($this->form->employeeRequest) && $this->form->employeeRequest->exists) {
                    $this->form->employeeRequest->update(['status' => 'APPROVED']); // Or 'SUBMITTED'
                }

                return redirect()
                    ->route('employee-requests.index')
                    ->with('success', __('forms.request_submitted'));
            }
        } catch (ValidationException $e) {
            // Re-throw to show validation errors on the form
            $this->dispatch('validation-error');
            throw $e;
        } catch (\App\Services\Cipher\Exceptions\CipherException $e) {
            report($e);
            $this->addError('signature', 'Помилка підписання: ' . $e->getMessage());
        } catch (\Exception $e) {
            report($e);
            $this->addError('signature', 'Не вдалося надіслати запит: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.employee.employee-create', [ // Corrected view name
            'pageTitle' => __('forms.add_employee'),
            'certificateAuthorities' => $this->certificateAuthorities,
        ]);
    }
}
