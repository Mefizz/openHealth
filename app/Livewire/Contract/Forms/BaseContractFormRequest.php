<?php

namespace App\Livewire\Contract\Forms;

use App\Rules\ContractRules\ValidEndDate;
use App\Rules\ContractRules\ValidStartDate;
use App\Rules\ValidIBAN;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class BaseContractFormRequest extends Form
{

    #[Validate([
        'contractor_payment_details.bank_name'     => 'required|string',
        'contractor_payment_details.payer_account' => ['required', 'string', new ValidIBAN],
        'contractor_payment_details.mfo'           => [
            'required_if:contractor_payment_details.payer_account,!regex:/^UA\d{22}$|^UA\d{27}$/',
            'string',
            'max:6'
        ],
    ])]
    public ?array $contractor_payment_details = [];

    #[Validate('required')]
    public ?object $statute_md5;

    #[Validate('required')]
    public ?object $additional_document_md5;
    #[Validate('required')]
    public ?array $contractor_divisions = [];
    #[Validate('required')]
    public ?string $contractor_base = '';

    public ?string $start_date = '';

    public ?string $end_date = '';

    #[Validate([
        'external_contractors.legal_entity_id'       => 'required',
        'external_contractors.contract.expires_at'       => 'required|date',
        'external_contractors.contract.issued_at'        => 'required|date',
        'external_contractors.contract.number'           => 'required|string',
        'external_contractors.divisions.id'            => 'required|string',
        'external_contractors.divisions.medical_service' => 'required|string',
    ])]
    public ?array $external_contractors = [];

    #[Validate('accepted')]
    public string $consent_text;

    public string $id_form = 'PMD_1';
    public string $previous_request_id = '';

    /**
     * @throws ValidationException
     */
    public function rulesForModelValidate(string $model = ''): array
    {
        $rules = $this->getRules();

        if (empty($model)) {
            $rules = array_filter($rules, function ($key) {
                return strpos($key, 'external_contractors') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            return $this->validate($rules);
        }

        return $this->validate($this->rulesForModel($model)->toArray());
    }

    protected function rules(): array
    {

        return [
            'start_date' => ['required', 'string', new ValidStartDate()],
            'end_date'   => ['required', 'string', new ValidEndDate($this->start_date)],
        ];
    }

    public function buildPayload(array $context): array
    {
        $data = $this->toArray();

        // Загальна логіка
        $data['additional_document_md5'] = md5_file($context['additional_document_md5_path']);
        $data['statute_md5'] = md5_file($context['statute_md5_path']);
        $data['end_date'] = Carbon::parse($this->end_date)->format('Y-m-d');
        $data['start_date'] = Carbon::parse($this->start_date)->format('Y-m-d');
        $data['contractor_owner_id'] = $context['legalEntity']->getOwner()->uuid;
        $data['contractor_payment_details']['payer_account'] = str_replace(' ', '', $data['contractor_payment_details']['payer_account']);

        unset($data['knedp'], $data['keyContainerUpload'], $data['password']); //
        return $data;
    }

}
