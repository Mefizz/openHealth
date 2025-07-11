<div>
    <x-section-navigation class="breadcrumb-form">
        {{-- The title now uses the generic $position variable and its party relationship --}}
        <x-slot name="title">{{ $pageTitle }} {{ $position->party->fullName ?? '' }}</x-slot>
    </x-section-navigation>

    <div class="form space-y-8">
        {{-- The fieldset is always disabled in a "show" view --}}
        <fieldset disabled class="space-y-8">
            @include('livewire.employee.parts.employee')
            @include('livewire.employee.parts.documents')
            @include('livewire.employee.parts.position')

            {{-- Doctor-specific fields --}}
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
            {{-- The "Back" button is now dynamic and goes to the correct list --}}
            @if($position instanceof \App\Models\Employee\Employee)
                <a href="{{ route('employee.index', ['legalEntity' => legalEntity()->id]) }}" class="button-minor">
                    &larr; {{ __('forms.backToList') }}
                </a>
            @else
                <a href="{{ route('employee-request.index', ['legalEntity' => legalEntity()->id]) }}" class="button-minor">
                    &larr; {{ __('forms.backToList') }}
                </a>
            @endif

            {{-- The "Edit" button logic --}}
            @can('update', $position)
                @if($position instanceof \App\Models\Employee\Employee)
                    <a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'employee' => $position]) }}" class="button-secondary">
                        {{__('forms.edit')}}
                    </a>
                @elseif($position instanceof \App\Models\Employee\EmployeeRequest)
                    <a href="{{ route('employee-request.edit', ['legalEntity' => legalEntity()->id, 'employee_request' => $position]) }}" class="button-secondary">
                        {{__('forms.edit')}}
                    </a>
                @endif
            @endcan
        </div>
    </div>
</div>
