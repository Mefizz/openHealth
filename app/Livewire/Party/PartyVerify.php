<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Livewire\Component;
use Log;

class PartyVerify extends Component
{
    public Party $party;
    public LegalEntity $legalEntity;
    public array $verificationDetails = [];

    public bool $showUpdateModal = false;

    public string $status = '';
    public string $reason = '';
    public string $comment = '';

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->legalEntity = $legalEntity;
        $this->party = $party;
        $this->loadVerificationDetails();
    }

    public function loadVerificationDetails(): void
    {
        try {
            $response = EHealth::party()->getDetails($this->party->uuid);
            $this->verificationDetails = $response->validate();

        } catch (\Exception $e) {
            session()->flash('error', 'Не вдалося завантажити деталі верифікації.');
        }
    }

    /**
     * Opens the update modal.
     */
    public function openUpdateModal(): void
    {
        $this->reset(['status', 'reason', 'comment']);
        $this->showUpdateModal = true;
    }

    /**
     * Closes the update modal.
     */
    public function closeModal(): void
    {
        $this->showUpdateModal = false;
    }

    /**
     * Handles the form submission to update the verification status.
     */
    public function updateStatus(): void
    {
        $this->validate([
                            'status' => 'required|string',
                            'reason' => 'required|string',
                            'comment' => 'nullable|string',
                        ]);

        $payload = [
            'dracs_death' => [
                'verification_status' => $this->status,
                'verification_reason' => $this->reason,
                'verification_comment' => $this->comment,
            ]
        ];

        try {
            EHealth::party()->update($this->party->uuid, $payload);

            session()->flash('success', 'Статус успішно оновлено.');

            $this->closeModal();
            $this->loadVerificationDetails();

        } catch (EHealthValidationException $e) {
            // Цей блок спеціально для помилок валідації 422
            Log::error('[PARTY UPDATE DEBUG] eHealth API returned a validation error.', [
                'party_uuid' => $this->party->uuid,
                'payload_sent' => $payload,
                'validation_errors' => $e->getErrors(), // Метод для отримання деталей помилки
            ]);
            session()->flash('error', 'Помилка валідації від ЕСОЗ. Перевірте логи для деталей.');
            $this->closeModal();
        } catch (\Exception $e) {
            // Цей блок для всіх інших помилок (немає зв'язку, помилка 500 і т.д.)
            Log::error('[PARTY UPDATE DEBUG] Request failed with a generic error.', [
                'party_uuid' => $this->party->uuid,
                'error_message' => $e->getMessage(),
            ]);
            session()->flash('error', 'Помилка під час оновлення статусу: ' . $e->getMessage());
            $this->closeModal();
        }
    }

    public function render()
    {
        return view('livewire.party.party-verify'); // <-- 2. ЗМІНИ ШЛЯХ ДО VIEW
    }
}
