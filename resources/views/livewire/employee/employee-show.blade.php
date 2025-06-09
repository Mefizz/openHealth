<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ $pageTitle }}: {{ $employee->fullName }}</x-slot>
    </x-section-navigation>

    <div class="p-4">
        {{--
            This is the magic part. The <fieldset disabled> wrapper will make all form
            elements inside it (inputs, selects, etc.) read-only without changing the partials.
        --}}
        <fieldset disabled class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
            {{-- We are re-using the exact same partials from the create/edit form --}}
            @include('livewire.employee._parts._employee')
            @include('livewire.employee._parts._documents')

            @if ($employee->employee_type === 'DOCTOR')
                <div class="mt-4">
                    @include('livewire.employee._parts._education')
                    @include('livewire.employee._parts._specialities')
                    @include('livewire.employee._parts._science_degree')
                    @include('livewire.employee._parts._qualifications')
                </div>
            @endif
        </fieldset>

        {{-- Action Buttons --}}
        <div class="mt-6 flex justify-between items-center">
            <a href="{{ route('employee.index') }}" class="button-minor">
                &larr; {{ __('forms.backToList') }}
            </a>
            <a href="{{ route('employee.edit', $employee->id) }}" class="button-primary">
                {{ __('forms.goToEdit') }}
            </a>
        </div>
    </div>
</div>
