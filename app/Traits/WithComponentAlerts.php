<?php

namespace App\Traits;

trait WithComponentAlerts
{
    public ?string $alertMessage = null;
    public string $alertType = 'success';

    /**
     * Checks if there is an active alert to be displayed.
     *
     * @return bool
     */
    public function hasAlert(): bool
    {
        return !is_null($this->alertMessage);
    }

    /**
     * Sets the alert message and type to be displayed.
     *
     * @param string $message The message to display.
     * @param string $type The type of the alert ('error' or 'success').
     */
    protected function showAlert(string $message, string $type = 'error'): void
    {
        $this->alertMessage = $message;
        $this->alertType = $type;
    }

    /**
     * Clears the alert message, causing it to disappear from the view.
     * This method is intended to be called via wire:click.
     */
    public function dismissAlert(): void
    {
        $this->alertMessage = null;
    }
}
