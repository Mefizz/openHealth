<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Attributes\On;

class FlashMessage extends Component
{
    public string $message = '';
    public string $type = 'success';
    public array $errors = [];
    public bool $visible = false;

    #[On('flashMessage')]
    public function showFlashMessage($data): void
    {
        $this->message = $data['message'] ?? '';
        $this->type = $data['type'] ?? 'success';
        $this->errors = $data['errors'] ?? [];
        $this->visible = true;

        $this->dispatch('start-flash-timer');
    }

    public function hideFlashMessage(): void
    {
        $this->visible = false;
    }

    #[On('show-notification')]
    public function showNotification($data): void
    {
        $this->message = $data['message'] ?? '';
        $this->type = $data['type'] ?? 'success';
        $this->errors = $data['errors'] ?? [];
        $this->visible = true;

        // This will automatically hide the message after 5 seconds
        $this->dispatch('start-flash-timer');
    }

    public function hideNotification(): void
    {
        $this->visible = false;
    }

    public function render(): View
    {
        return view('livewire.components.flash-message');
    }
}
