<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

use AllowDynamicProperties;
use App\Models\Contract;
use App\Rules\ContractRules\ValidEndDate;
use App\Rules\ContractRules\ValidStartDate;
use App\Rules\ValidIBAN;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Carbon\Carbon;

/**
 * @property-read Contract $model
 */
#[AllowDynamicProperties]
abstract class BaseContractFormRequest extends Form
{
    #[Validate('required')]
    public ?string $knedp = null;

    #[Validate('required|file')]
    public ?TemporaryUploadedFile $keyContainerUpload = null;

    #[Validate('required|string|min:4')]
    public ?string $password = null;

    // --- Common eHealth Payload Fields ---

    #[Validate('required|string')]
    public ?string $contractor_base = '';

    public ?string $previous_request_id = null;

    #[Validate([
        'contractor_payment_details.bank_name' => 'required|string',
        'contractor_payment_details.payer_account' => ['required', 'string', new ValidIBAN()],
        'contractor_payment_details.mfo' => [
            'required_if:contractor_payment_details.payer_account,!regex:/^UA\d{22}$|^UA\d{27}$/',
            'string',
            'max:6'
        ],
    ])]
    public array $contractor_payment_details = [
        'bank_name' => '',
        'payer_account' => '',
        'mfo' => '',
    ];

    #[Validate('required|date_format:Y-m-d')]
    public ?string $start_date = '';

    #[Validate('required|date_format:Y-m-d')]
    public ?string $end_date = '';

    public ?string $id_form = '';

    #[Validate('sometimes|string')]
    public ?string $contract_number = null;

    #[Validate('required|file')]
    public ?object $statute_md5;

    #[Validate('required|file')]
    public ?object $additional_document_md5;

    #[Validate('accepted')]
    public bool $consent_text = false;

    /**
     * Get rules for KEP (signature) fields only.
     */
    public function rulesForKepOnly(): array
    {
        return new \ReflectionClass($this)->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * Set the model instance.
     */
    public function setModel(Contract $contract): void
    {
        $this->model = $contract;
        $this->fill($contract->toArray());

        // Handle file uploads - they won't be in the model
        $this->statute_md5 = null;
        $this->additional_document_md5 = null;
    }

    /**
     * Validate the form data.
     *
     * @throws ValidationException
     */
    public function rulesForModelValidate(string $model = ''): array
    {
        $rules = $this->getRules();

        if (empty($model)) {
            $rules = array_filter($rules, function ($key) {
                return !str_starts_with($key, 'external_contractors');
            }, ARRAY_FILTER_USE_KEY);

            return $this->validate($rules);
        }

        return $this->validate($this->rulesForModel($model)->toArray());
    }

    /**
     * Add custom date validation rules.
     */
    protected function rules(): array
    {
        return [
            'start_date' => ['required', 'string', 'date_format:Y-m-d', new ValidStartDate()],
            'end_date' => ['required', 'string', 'date_format:Y-m-d', new ValidEndDate($this->start_date)],
        ];
    }

    /**
     * Build the base payload for eHealth.
     * This method is called by the child classes.
     *
     * @param  array  $context  Contextual data (e.g., owner ID, dictionaries)
     * @return array
     */
    public function buildPayload(array $context): array
    {
        $data = $this->toArray();

        // Add common data from context
        $data['contractor_owner_id'] = $context['contractor_owner_id'];

        // Format common data
        $data['contractor_payment_details']['payer_account'] = str_replace(' ', '', $data['contractor_payment_details']['payer_account']);
        $data['start_date'] = Carbon::parse($this->start_date)->format('Y-m-d');
        $data['end_date'] = Carbon::parse($this->end_date)->format('Y-m-d');

        // This is a placeholder. Per the flow, we must FIRST call initialize,
        // get back upload URLs, upload the files, and *then* eHealth knows the MD5.
        // For now, we'll send the MD5 of the uploaded file as per your old logic.
        // TODO: Refactor this when implementing Document Storage upload.
        $data['statute_md5'] = md5_file($this->statute_md5->getRealPath());
        $data['additional_document_md5'] = md5_file($this->additional_document_md5->getRealPath());

        unset(
            $data['knedp'],
            $data['keyContainerUpload'],
            $data['password'],
            $data['model']
        );

        return $data;
    }
}
