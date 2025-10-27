<div>
    <div>
        @include('livewire.employee.employee')
    </div>
    @php
        $pageTitle = __('forms.add_employee');
    @endphp

    @include('livewire.employee.employee', ['pageTitle' => $pageTitle])
</div>
