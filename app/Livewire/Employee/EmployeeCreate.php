<?php

namespace App\Livewire\Employee;

use App\Livewire\LegalEntity\LegalEntity;
use App\Models\Employee\EmployeeRequest;
use App\Models\Relations\Party;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeCreate extends EmployeeComponent
{
    /**
     * Завантаження словників через getDictionary з FormTrait
     */
    public function mount(): void
    {
        parent::mount();
    }

    /**
     * Зберігає нового співробітника
     */
//    public function save(): void
//    {
//        $validated = $this->validate(); // Livewire-валидація
//        $snakeCaseData = $this->convertArrayKeysToSnakeCase($validated); // з FormTrait
//        $data = $this->serializeFormData($snakeCaseData);
//
//        DB::transaction(function () use ($data) {
//            $party = $this->createParty($data);
//            $this->createEmployeeRequest($party, $data);
//            $this->createUser($party);
//            $this->createDocuments($party);
//            $this->createEducations($party);
//            $this->createSpecialities($party);
//            $this->createScienceDegree($party, $data);
//            $this->createQualifications($party);
//        });
//
//        session()->flash('success', __('forms.saved_successfully'));
//    }
//
//    /**
//     * Підготовка даних перед збереженням
//     */
//    protected function serializeFormData(array $validated): array
//    {
//        return [
//            'party'           => $validated['party'] ?? [],
//            'science_degree'  => $validated['science_degree'] ?? [],
//            // документи тощо залишаються у $this->form
//        ];
//    }
//
//    // ──────────────── Методи створення ────────────────
//
//    protected function createParty(array $data): Party
//    {
//        return Party::create($data['party']);
//    }
//
//    protected function createEmployeeRequest(Party $party, array $data): void
//    {
//        EmployeeRequest::create([
//            'party_id'      => $party->id,
//            'position'      => $data['party']['position'] ?? null,
//            'employee_type' => $data['party']['employee_type'] ?? null,
//            'start_date'    => $data['party']['start_date'] ?? now()->toDateString(),
//            'status'        => 'NEW',
//            'inserted_at'   => now(),
//        ]);
//    }
//
//    protected function createUser(Party $party): void
//    {
//        User::create([
//            'email'           => $party->email,
//            'password'        => Hash::make(Str::random(12)),
//            'legal_entity_id' => app(LegalEntity::class)?->id,
//            'person_id'       => $party->id,
//        ]);
//    }
//
//    protected function createDocuments(Party $party): void
//    {
//        foreach ($this->form->documents ?? [] as $docData) {
//            $party->documents()->create($docData);
//        }
//    }
//
//    protected function createEducations(Party $party): void
//    {
//        foreach ($this->form->educations ?? [] as $educationData) {
//            $party->educations()->create($educationData);
//        }
//    }
//
//    protected function createSpecialities(Party $party): void
//    {
//        foreach ($this->form->specialities ?? [] as $specialityData) {
//            $party->specialities()->create($specialityData);
//        }
//    }
//
//    protected function createScienceDegree(Party $party, array $data): void
//    {
//        if (!empty($data['science_degree'])) {
//            $party->science()->create($data['science_degree']);
//        }
//    }
//
//    protected function createQualifications(Party $party): void
//    {
//        foreach ($this->form->qualifications ?? [] as $qualificationData) {
//            $party->qualifications()->create($qualificationData);
//        }
//    }

    public function save(): void
    {
        $validated = $this->validate(); // валідація з Livewire

        DB::transaction(function () use ($validated) {
            // 1. Зберігаємо party
            $party = Party::create($validated['party']);

            // 2. Зберігаємо employee request
            EmployeeRequest::create([
                'party_id'      => $party->id,
                'position'      => $validated['party']['position'] ?? null,
                'employee_type' => $validated['party']['employee_type'] ?? null,
                'start_date'    => $validated['party']['start_date'] ?? now()->toDateString(),
                'status'        => 'NEW',
                'inserted_at'   => now(),
            ]);

            // 3. Зберігаємо користувача
            User::create([
                'email'           => $party->email,
                'password'        => Hash::make(Str::random(12)),
                'legal_entity_id' => app(LegalEntity::class)?->id,
                'person_id'       => $party->id,
            ]);

            // 4. Зберігаємо документи
            foreach ($this->form->documents ?? [] as $docData) {
                $party->documents()->create($docData);
            }

            // 5. Освіта
            foreach ($this->form->educations ?? [] as $educationData) {
                $party->educations()->create($educationData);
            }

            // 6. Спеціальності
            foreach ($this->form->specialities ?? [] as $specialityData) {
                $party->specialities()->create($specialityData);
            }

            // 7. Вчена ступінь
            if (!empty($this->form->science_degree)) {
                $party->science()->create($this->form->science_degree);
            }

            // 8. Кваліфікації
            foreach ($this->form->qualifications ?? [] as $qualificationData) {
                $party->qualifications()->create($qualificationData);
            }
        });

        session()->flash('success', __('forms.saved_successfully'));
    }


    public function render()
    {

        $pageTitle = __('forms.add_employee');

        return view('livewire.employee.employee-create', compact('pageTitle'));
    }
}
