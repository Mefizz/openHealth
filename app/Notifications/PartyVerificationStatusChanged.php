<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Relations\Party;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PartyVerificationStatusChanged extends Notification
{
    use Queueable;

    public Party $party;
    public string $newStatus;

    /**
     * Create a new notification instance.
     * @param Party $party The party whose status has changed.
     * @param string $newStatus The new verification status.
     */
    public function __construct(Party $party, string $newStatus)
    {
        $this->party = $party;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the notification's delivery channels.
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     * @param mixed $notifiable
     * @return array
     */
    public function toArray(mixed $notifiable): array
    {
//        $legalEntity = $this->party->employees->first()->legalEntity ?? null;

        return [
            'message' => 'Статус вашої верифікації було змінено на: ' . $this->newStatus,
//            'link' => $legalEntity ? route('party.verification.show', [
//                'legalEntity' => $legalEntity->id,
//                'party' => $this->party->id
//            ]) : null,
        ];
    }
}
