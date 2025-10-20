<div>
    <x-header-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('forms.add_employee') }} {{ $employee->party->fullName ?? '' }}
        </x-slot>
    </x-header-navigation>
    @php
        $pageTitle = __('forms.add_employee');
    @endphp

    @include('livewire.employee.employee', ['pageTitle' => $pageTitle])
</div>
