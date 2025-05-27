<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ $pageTitle }}</x-slot>
        <x-slot name="status">
            <span class="status-badge status-{{ $employeeRequest->status }}">
                {{ __("statuses.{$employeeRequest->status}") }}
            </span>
        </x-slot>
    </x-section-navigation>

    <section class="section-view">
        @include('livewire.employee-request._parts._view-employee')
        @include('livewire.employee-request._parts._view-documents')

        @if($isDoctor)
            <div>
                @include('livewire.employee-request._parts._view-education')
                @include('livewire.employee-request._parts._view-specialities')
                @include('livewire.employee-request._parts._view-science-degree')
                @include('livewire.employee-request._parts._view-qualifications')
            </div>
        @endif

        <div class="form-button-group">
            @if($employeeRequest->status === 'new')
                <button type="button" class="button-primary" wire:click="edit">
                    {{ __('forms.edit') }}
                </button>
            @endif
        </div>
    </section>
</div>
