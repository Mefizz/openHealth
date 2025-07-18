@php
    $user = auth()->user();
    $hasActions = false;

    if ($position instanceof \App\Models\Employee\Employee) {
        if (
            $user->can('view', $position) ||
            $user->can('update', $position) ||
            ($user->can('dismiss', $position) && $position->status?->value === \App\Enums\Status::APPROVED->value)
        ) {
            $hasActions = true;
        }
    } elseif ($position instanceof \App\Models\Employee\EmployeeRequest) {
        if (
            $user->can('view', $position) ||
            $user->can('update', $position) ||
            ($user->can('delete', $position) && !$position->uuid)
        ) {
            $hasActions = true;
        }
    }
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
            @click="
            @if($hasActions)
                open = !open
            @else
                $wire.dispatch('flashMessage', { message: '{{ __('forms.no_actions_available') }}', type: 'error' })
            @endif
        "
            class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white" type="button">
        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
    </button>

    @if($hasActions)
        <div x-show="open" x-transition class="absolute right-0 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600" style="display: none;">

            @if($position instanceof \App\Models\Employee\Employee)
                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                    @can('view', $position)
                        <li><a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'employee']) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">{{ __('forms.view') }}</a></li>
                    @endcan
                    @can('update', $position)
                        <li><a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'employee']) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">{{ __('forms.edit') }}</a></li>
                    @endcan
                </ul>
                @if($position->status?->value === 'APPROVED' && $canDismissEmployee)
                    <div class="py-1">
                        <button type="button" @click="open = false" wire:click="showModalDismissed({{ $position->id }})" class="block w-full text-left py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">{{ __('forms.dismiss') }}</button>
                    </div>
                @endif

            @elseif($position instanceof \App\Models\Employee\EmployeeRequest)
                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                    @can('view', $position)
                        {{-- THE FIX: Both links now point to the 'employee.show' route --}}
                        <li><a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'request']) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">{{ __('forms.view') }}</a></li>
                    @endcan
                    @can('update', $position)
                        {{-- THE FIX: Both links now point to the 'employee.edit' route --}}
                        <li><a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'request']) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">{{ __('forms.edit') }}</a></li>
                    @endcan
                </ul>
                @if(!$position->uuid && $canDeleteEmployeeRequest)
                    <div class="py-1">
                        <button type="button" @click="open = false" wire:click="confirmRequestDeletion({{ $position->id }})" class="block w-full text-left py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">{{ __('forms.delete') }}</button>
                    </div>
                @endcan
            @endif
        </div>
    @endif
</div>
