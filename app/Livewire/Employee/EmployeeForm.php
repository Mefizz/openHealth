<?php

namespace App\Livewire\Employee;

use App\Classes\Cipher\Traits\Cipher;
use App\Livewire\Employee\Forms\EmployeeForm as Form;
use App\Livewire\LegalEntity\LegalEntity;
use App\Repositories\EmployeeRepository;
use App\Traits\FormTrait;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeForm extends Component
{
    use FormTrait, Cipher, WithFileUploads, InteractsWithCache;

    const CACHE_PREFIX = 'register_employee_form';

    public Form $employeeRequest;
    public LegalEntity $legalEntity;
    public string $mode = 'create';
    public array $success = ['message' => '', 'status' => false];
    public ?array $error = ['message' => '', 'status' => false];
    public ?array $dictionaryNames = [
        'PHONE_TYPE',
        'COUNTRY',
        'SETTLEMENT_TYPE',
        'SPECIALITY_TYPE',
        'DIVISION_TYPE',
        'SPECIALITY_LEVEL',
        'GENDER',
        'QUALIFICATION_TYPE',
        'SCIENCE_DEGREE',
        'DOCUMENT_TYPE',
        'SPEC_QUALIFICATION_TYPE',
        'EMPLOYEE_TYPE',
        'POSITION',
        'EDUCATION_DEGREE',
        'EMPLOYEE_TYPE',
    ];

    public ?object $divisions;
    protected ?EmployeeRepository $employeeRepository;
    public string $employeeCacheKey;
    public string $requestId;
    public string $employeeId;
    public mixed $singleProperty;
    public ?object $file = null;

    public function boot(EmployeeRepository $employeeRepository): void
    {
        $this->employeeRepository = $employeeRepository;
        $this->employeeCacheKey = self::CACHE_PREFIX . '-' . Auth::user()->legalEntity->uuid;
    }

    public function mount(Request $request, $id = ''): void
    {
        $this->legalEntity = Auth::user()->legalEntity;
        $this->requestId = $request->input('storeId', '');
        $this->employeeId = $id;

        $this->setCertificateAuthority();
        $this->getDictionaries();
    }

    public function setCertificateAuthority(): void
    {
        $this->getCertificateAuthority = $this->getCertificateAuthority();
    }

    public function create(string $model, string $singleProperty = ''): void
    {
        $this->mode = 'create';
        $this->singleProperty = $singleProperty;
        $this->employeeRequest->{$model} = [];
        $this->openModal($model);
        $this->dictionaryUnset();
    }

    public function store(string $model, array $modelSingle = []): void
    {
        $rules = $modelSingle ?: $model;
        $this->employeeRequest->rulesForModelValidate($rules);
        $this->resetErrorBag();

        if (!empty($modelSingle)) {
            $this->employeeRequest->{$model} = $this->employeeRequest->{$modelSingle};
            unset($this->employeeRequest->{$modelSingle});
        }

        $this->storeCacheEmployee($model);
        $this->closeModalModel();
        $this->dispatch('flashMessage', ['message' => __('Інформацію успішно оновлено'), 'type' => 'success']);
    }

    protected function storeCacheEmployee(string $model): void
    {
        $this->storeCacheData(
            $this->employeeCacheKey,
            $model,
            'employeeRequest',
            ['party', 'scienceDegree']
        );
    }

    public function render()
    {
        return view('livewire.employee.employee-create');
    }
}
