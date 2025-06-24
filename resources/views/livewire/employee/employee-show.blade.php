<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ $pageTitle }} {{ $employee->fullName }}</x-slot>
    </x-section-navigation>

    <div class="form space-y-8">
            <fieldset disabled class="space-y-8">
                @include('livewire.employee.parts.employee')
                @include('livewire.employee.parts.documents')
                @include('livewire.employee.parts.position')

                @if ($form->employeeType === 'DOCTOR')
                    <div class="space-y-8">
                        @include('livewire.employee.parts.education')
                        @include('livewire.employee.parts.specialities')
                        @include('livewire.employee.parts.science_degree')
                        @include('livewire.employee.parts.qualifications')
                    </div>
                @endif
            </fieldset>

        <div class="mt-6 flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-6">
            <a href="{{ url()->previous() }}" class="button-minor">
                &larr; {{ __('forms.backToList') }}
            </a>

            @can('update', $employee)
                @if($employee instanceof \App\Models\Employee\EmployeeRequest)
                    <a href="{{ route('employee-request.edit', $employee) }}" class="button-secondary">
                        {{__('forms.edit')}}
                    </a>
                @endif
            @endcan
        </div>
    </div>
</div>
