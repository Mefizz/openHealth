<?php

namespace App\Traits;

use App\Models\Relations\Address;
use App\Classes\eHealth\Api\AdressesApi;
use App\View\Components\Forms\AddressesSearch;
use Illuminate\Validation\ValidationException;

trait AddressSearch
{
    public ?array $address = [
        'country' => Address::DEFAULT_COUNTRY,
        'type' => Address::DEFAULT_TYPE
    ];

    public ?array $districts = [];

    public ?array $settlements = [];

    public ?array $streets = [];

    public function addressValidation(): array
    {
        $errors = [];

        try {
            $this->validate(AddressesSearch::getAddressRules($this->address), AddressesSearch::getAddressMessages());
        } catch (ValidationException $err) {
            $errors = $err->validator->errors()->toArray();
        }

        return $errors;
    }

    public function setAddressesFields($addresses)
    {
        $this->updatedFields($addresses);
    }

    protected function updatedFields($addresses)
    {
        foreach ($addresses as $key => $address) {
            if (!empty($address)) {
                $this->address[$key] = $address;
            }
        }
    }

    public function updateAddressRegion($value)
    {
        $this->districts = [];

        if (strlen($value) > 2) {
            $this->getDistricts();
        }
   }

    public function updateAddressStreet($value)
    {
        $this->streets = [];

        if (strlen($value) > 2) {
            $this->getStreets();
        }
    }

    public function updateAddressSettlement($value)
    {
        $this->settlements = [];

        if (strlen($value) > 2) {
            $this->getSettlements();
        }
    }

    public function selectStreets($name)
    {
        $this->address['street'] = $name;
        $this->streets = [];
    }

    public function selectSettlements($name, $id)
    {
        $this->address['settlement'] = $name;
        $this->address['settlementId'] = $id;
        $this->settlements = [];
    }

    public function getAddressData(): array
    {
        return [
            'address' => $this->address,
            'rules' => $this->getAddressRules(),
            'messages' => $this->getAddressMessages()
        ];
    }

    public function getDistricts(): void
    {
        if (empty($this->address['area'])) {
            return;
        }

        $this->districts = AdressesApi::_districts($this->address['area'], $this->address['region'])['data'] ?? [];
    }

    public function getSettlements(): void
    {
        if (empty($this->address['region'])) {
            return;
        }

        $this->settlements = AdressesApi::_settlements(
            $this->address['area'],
            $this->address['region'],
            $this->address['settlement'])['data'] ?? [];
    }

    public function getStreets(): void
    {
        if (empty($this->address['settlementId'])) {
            return;
        }

        $this->streets = AdressesApi::_streets(
            $this->address['settlementId'],
            $this->address['streetType'],
            $this->address['street']
        )['data'] ?? [];
    }
}
