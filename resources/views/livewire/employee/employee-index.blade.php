<div>
    <x-section-navigation x-data="{ showFilter: false }">

        <x-slot name="title">
            {{ __('forms.employees') }}
        </x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col">
                <div class="flex flex-wrap items-end justify-between gap-4"> <div class="flex items-end gap-4"> <div class="w-96">
                            <x-forms.form-group>
                                <x-slot name="label">
                                    <label for="employee_search" class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400"
                                             aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg"
                                             fill="none"
                                             viewBox="0 0 20 20"
                                        >
                                            <path stroke="currentColor"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                        </svg>
                                        <span>{{ __('forms.employeeSearch') }}</span>
                                    </label>
                                </x-slot>
                                <x-slot name="input">
                                    <div class="form-group group w-full relative top-[12px]">
                                        <input type="text"
                                               id="employee_search"
                                               placeholder=" "
                                               class="input peer"
                                               wire:model.live.debounce.300ms="search"
                                               autocomplete="off" />
                                        <label for="employee_search" class="label">ПІБ</label>
                                    </div>
                                </x-slot>
                            </x-forms.form-group>
                        </div>

                        <button class="flex items-center gap-2 gray-button relative top-[8px]" @click="showFilter = !showFilter">
                            <svg width="16" height="16" id="svg-adjustments" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.00003 3.1999C4.00003 2.98773 3.91574 2.78425 3.76571 2.63422C3.61568 2.48419 3.41220 2.39990 3.20003 2.39990C2.98785 2.39990 2.78437 2.48419 2.63434 2.63422C2.48431 2.78425 2.40003 2.98773 2.40003 3.1999V9.0143C2.15682 9.15474 1.95485 9.35672 1.81444 9.59994C1.67402 9.84316 1.60010 10.11910 1.60010 10.39990C1.60010 10.68070 1.67402 10.95660 1.81444 11.1999C1.95485 11.44310 2.15682 11.64510 2.40003 11.7855V12.7999C2.40003 13.01210 2.48431 13.21560 2.63434 13.3656C2.78437 13.51560 2.98785 13.59990 3.20003 13.5999C3.41220 13.59990 3.61568 13.51560 3.76571 13.3656C3.91574 13.21560 4.00003 13.01210 4.00003 12.7999V11.7855C4.24324 11.64510 4.44520 11.44310 4.58562 11.1999C4.72603 10.95660 4.79996 10.68070 4.79996 10.39990C4.79996 10.11910 4.72603 9.84316 4.58562 9.59994C4.44520 9.35672 4.24324 9.15474 4.00003 9.0143V3.1999ZM8.80003 3.1999C8.80003 2.98773 8.71574 2.78425 8.56571 2.63422C8.41568 2.48419 8.21220 2.39990 8.00003 2.39990C7.78785 2.39990 7.58437 2.48419 7.43434 2.63422C7.28431 2.78425 7.20003 2.98773 7.20003 3.1999V4.2143C6.95682 4.35474 6.75485 4.55672 6.61444 4.79994C6.47402 5.04316 6.40010 5.31906 6.40010 5.59990C6.40010 5.88075 6.47402 6.15665 6.61444 6.39987C6.75485 6.64309 6.95682 6.84507 7.20003 6.9855V12.7999C7.20003 13.01210 7.28431 13.21560 7.43434 13.3656C7.58437 13.51560 7.78785 13.59990 8.00003 13.5999C8.21220 13.59990 8.41568 13.51560 8.56571 13.3656C8.71574 13.21560 8.80003 13.01210 8.80003 12.7999V6.9855C9.04324 6.84507 9.24520 6.64309 9.38562 6.39987C9.52603 6.15665 9.59996 5.88075 9.59996 5.59990C9.59996 5.31906 9.52603 5.04316 9.38562 4.79994C9.24520 4.55672 9.04324 4.35474 8.80003 4.2143V3.1999ZM12.8 2.3999C13.0122 2.3999 13.2157 2.48419 13.3657 2.63422C13.5157 2.78425 13.6 2.98773 13.6 3.1999V9.0143C13.8432 9.15474 14.0452 9.35672 14.1856 9.59994C14.3260 9.84316 14.4 10.11910 14.4 10.39990C14.4 10.68070 14.3260 10.95660 14.1856 11.1999C14.0452 11.44310 13.8432 11.64510 13.6 11.7855V12.7999C13.6 13.01210 13.5157 13.21560 13.3657 13.3656C13.2157 13.51560 13.0122 13.59990 12.8 13.5999C12.5879 13.59990 12.3844 13.51560 12.2343 13.3656C12.0843 13.21560 12 13.01210 12 12.7999V11.7855C11.7568 11.64510 11.5549 11.44310 11.4144 11.1999C11.2740 10.95660 11.2001 10.68070 11.2001 10.39990C11.2001 10.11910 11.2740 9.84316 11.4144 9.59994C11.5549 9.35672 11.7568 9.15474 12 9.0143V3.1999C12 2.98773 12.0843 2.78425 12.2343 2.63422C12.3844 2.48419 12.5879 2.39990 12.8 2.3999V2.3999Z"
                                    fill="currentColor"/>
                            </svg>
                            <span>{{ __('patients.additional_search_parameters') }}</span>
                        </button>
                    </div>

                    <div class="flex items-center space-x-2 pt-5">
                        <a href="{{ route('employee.create', ['legalEntity' => legalEntity()->id]) }}" class="button-primary">
                            {{ __('forms.newEmployee') }}
                        </a>
                        <button wire:click="syncEmployees" type="button" class="button-sync">
                            {{ __('forms.synchronise_with_eHealth') }}
                        </button>
                    </div>
                </div>

                <div x-show="showFilter" x-transition class="pt-4 mt-4">
                    <div class="form-row-4">
                        <div class="form-group phone-wrapper">
                            <input wire:model.live.debounce.300ms="filter.phone"
                                   type="tel"
                                   placeholder=" "
                                   class="peer input pl-10 with-leading-icon text-gray-500"
                                   x-model="phones[index].number"
                                   x-mask="+380999999999"
                                   :id="$id('phone', '_number' + index)"
                                   :class="{ 'input-error border-red-500': errors[legalEntityForm.phones.${index}.number] }"
                            />
                            <label for="filter_phone" class="label pl-10">Телефон</label>
                        </div>
                        <div class="form-group group">
                            <input wire:model.live.debounce.300ms="filter.email"
                                   type="email"
                                   name="filter_email"
                                   id="filter_email"
                                   class="input peer"
                                   placeholder=" "
                                   autocomplete="off"
                            />
                            <label for="filter_email" class="label">Email</label>
                        </div>
                    </div>
                    <div class="form-row-4">
                        <div class="form-group group">
                            <select wire:model.live="filter.role"
                                    name="filter_role"
                                    id="filter_role"
                                    class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                <option value="">Всі ролі</option>
                                @foreach($dictionaries['EMPLOYEE_TYPE'] ?? [] as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            <label for="filter_role" class="label">Роль працівника</label>
                        </div>
                        <div class="form-group group">
                            <select wire:model.live="filter.position"
                                    name="filter_position"
                                    id="filter_position"
                                    class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                <option value="">Всі посади</option>
                                @foreach($dictionaries['POSITION'] ?? [] as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            <label for="filter_position" class="label">Посада</label>
                        </div>
                    </div>
                    <div class="form-row-4">
                        <div class="form-group group">
                            <select wire:model.live="filter.division_id"
                                    name="filter_division"
                                    id="filter_division"
                                    class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                <option value="">Всі підрозділи</option>
                                @foreach($divisions ?? [] as $division)
                                    <option value="{{ $division->id }}">{{ $division->name }}</option>
                                @endforeach
                            </select>
                            <label for="filter_division" class="label">Медичний заклад</label>
                        </div>

                        <div class="form-group group">
                            <select wire:model.live="status"
                                    name="filter_status"
                                    id="filter_status"
                                    class="input peer text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                            >
                                <option value="">Всі статуси</option>
                                <option value="APPROVED">Активний</option>
                                <option value="DISMISSED">Звільнений</option>
                            </select>
                            <label for="filter_status" class="label">Статус у системі</label>
                        </div>
                    </div>
                    <div class="w-full flex justify-start mt-4">
                        <button type="button" wire:click="resetFilters" class="button-primary">Скинути всі фільтри</button>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-section-navigation>

    <x-section>
        <div class="space-y-6">
            @forelse($parties as $party)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" wire:key="party-{{ $party->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $party->fullName }}</h3>
                            <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 mt-2">
                                @if ($mobilePhone = $party->phones->firstWhere('type', 'MOBILE'))
                                    <span class="flex items-center gap-1.5">
                                       <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.427 14.768 17.2 13.542a1.733 1.733 0 0 0-2.45 0l-.613.613a1.732 1.732 0 0 1-2.45 0l-1.838-1.84a1.735 1.735 0 0 1 0-2.452l.612-.613a1.735 1.735 0 0 0 0-2.452L9.237 5.572a1.6 1.6 0 0 0-2.45 0c-3.223 3.2-1.702 6.896 1.519 10.117 3.22 3.221 6.914 4.745 10.12 1.535a1.601 1.601 0 0 0 0-2.456Z"/>
                                         </svg>
                                        <a href="tel:{{ $mobilePhone->number }}" class="hover:underline">{{ $mobilePhone->number }}</a>
                                    </span>
                                @endif
                                @if($party->email)
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                           <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m3.5 5.5 7.893 6.036a1 1 0 0 0 1.214 0L20.5 5.5M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                                        </svg>
                                        <a href="mailto:{{$party->email}}" class="hover:underline">{{ $party->email }}</a>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('employee.add-position', ['legalEntity' => legalEntity()->id, 'party' => $party->id]) }}"
                               class="item-add text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <span class="text-xl leading-none">+</span>
                                <span>{{ __('forms.addPosition') }}</span>
                            </a>
                        </div>
                    </div>
                    <div class="flow-root mt-4">
                        <div class="max-w-screen-xl">
                        <table class="table-input w-full table-fixed">
                            <thead class="thead-input">
                            <tr>
                                <th scope="col" class="th-input w-[28%]">Посада</th>
                                <th scope="col" class="th-input w-[22%]">Роль</th>
                                <th scope="col" class="th-input w-[20%]">Підрозділ</th>
                                <th scope="col" class="th-input w-[15%]">Статус</th>
                                <th scope="col" class="th-input w-[15%] text-center">Дії</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $positions = $party->employees->merge($party->employeeRequests); @endphp
                            @foreach($positions as $position)
                                <tr>
                                    <td class="td-input break-words whitespace-normal align-top">{{ $dictionaries['POSITION'][$position->position] ?? $position->position }}</td>
                                    <td class="td-input break-words whitespace-normal align-top">{{ $dictionaries['EMPLOYEE_TYPE'][$position->employee_type] ?? $position->employee_type }}</td>
                                    <td class="td-input break-words whitespace-normal align-top">{{ $position->division->name ?? 'N/A' }}</td>

                                    <td class="td-input break-words whitespace-normal align-top">
                                        @switch($position->status)
                                            @case(\App\Enums\Status::APPROVED)
                                                <span class="badge-green">Активний</span>
                                                @break
                                            @case(\App\Enums\Status::DISMISSED)
                                                <span class="badge-red">Звільнений</span>
                                                @break
                                            @case(\App\Enums\Status::NEW)
                                                <span class="badge-red">Чернетка</span>
                                                @break
                                            @default
                                                <span class="badge-yellow">{{ $position->status }}</span>
                                        @endswitch
                                    </td>

                                    <td class="td-input text-center">
                                        <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                            <button @click="open = !open" class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white" type="button">
                                                <svg class="w-6 h-6 text-gray-800 dark:text-white svg-hover-action" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                                </svg>
                                            </button>
                                            <div x-show="open" x-transition class="absolute right-0 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600" style="display: none;">
                                                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                                                    <li>
                                                        <a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => $position->type]) }}"
                                                           class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true"
                                                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                <path stroke="currentColor" stroke-width="2"
                                                                      d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                                                                <path stroke="currentColor" stroke-width="2"
                                                                      d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                            </svg>
                                                            Переглянути
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => $position->type]) }}"
                                                           class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                                 fill="none" viewBox="0 0 24 24">
                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                                            </svg>
                                                            Редагувати
                                                        </a>
                                                    </li>
                                                </ul>
                                                @if($position->type === 'employee' && $position->status === \App\Enums\Status::APPROVED)
                                                    <div class="py-1" @click="open = false">
                                                        <button type="button"
                                                                wire:click="showModalDismissed({{ $position->id }})"
                                                                class="flex items-center gap-2 w-full py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                            <svg class="w-5 h-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                                 fill="none" viewBox="0 0 24 24">
                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                            </svg>
                                                            {{ __('forms.dismissed') }}
                                                        </button>
                                                    </div>
                                                @endif
                                                @if($position->type === 'request' && !$position->uuid)
                                                    <div class="py-1" @click="open = false">
                                                        <button type="button" wire:click="confirmRequestDeletion({{ $position->id }})" class="block w-full text-right py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                            {{ __('forms.delete') }}
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16">
                    <p class="text-gray-500 dark:text-gray-400 text-lg">{{__('Нічого не знайдено')}}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $parties->links() }}
        </div>
    </x-section>

    {{-- MODAL FOR DISMISSAL --}}
    <div x-data="{ showDismissModal: @entangle('showModal') }">
        <template x-teleport="body">
            <div x-show="showDismissModal" style="display: none" @keydown.escape.prevent.stop="showDismissModal = false" role="dialog" aria-modal="true" class="fixed inset-0 z-50 overflow-y-auto">
                <div x-show="showDismissModal" x-transition.opacity class="fixed inset-0 bg-black/30"></div>
                <div x-show="showDismissModal" x-transition @click="showDismissModal = false" class="relative flex min-h-screen items-center justify-center p-4">
                    <div @click.stop x-trap.noscroll.inert="showDismissModal" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            <span x-text="$wire.dismissal_employee_name || 'Підтвердження дії'"></span> - звільнення
                        </h2>
                        <p class="mt-4 text-sm text-gray-600 whitespace-pre-line dark:text-gray-300" x-text="$wire.dismiss_text"></p>
                        <div class="mt-6 flex justify-center gap-4">
                            <button type="button" @click="showDismissModal = false" wire:click="closeModal" class="button-primary">Скасувати</button>
                            <button type="button" wire:click="dismissed({{ $dismissed_id }})" wire:loading.attr="disabled" class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Звільнити</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- MODAL FOR DELETING A DRAFT --}}
    <div x-data="{ show: @entangle('showDeleteModal') }">
        <template x-teleport="body">
            <div x-show="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" style="display: none;">
                <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/30"></div>
                <div x-show="show" x-transition @click="show = false" class="relative flex min-h-screen items-center justify-center p-4">
                    <div @click.stop x-trap.noscroll.inert="show" class="relative w-full max-w-md overflow-hidden rounded-lg bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Видалення чернетки для <span x-text="$wire.deleteRequestName || 'співробітника'"></span>
                        </h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-text="$wire.deleteRequestText"></p>
                        <div class="mt-6 flex justify-center gap-4">
                            <button type="button" @click="show = false" class="button-primary">Скасувати</button>
                            <button type="button" wire:click="deleteRequest" wire:loading.attr="disabled" class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Видалити</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
