<div>
    <x-header-navigation class="items-start">

        <x-slot name="title">
            {{ __('employee-role.role') }}
        </x-slot>

        <div class="mt-3 ml-0 flex flex-col sm:flex-row sm:flex-wrap gap-2 self-start">
            <a href="{{ route('employee-request.create', ['legalEntity' => legalEntity()->id]) }}"
               class="button-primary">{{ __('employee-role.new_employee_role') }}</a>
            <button wire:click="sync" type="button" class="button-sync flex items-center gap-2 whitespace-nowrap">
                @icon('refresh', 'w-4 h-4')
                {{ __('forms.synchronise_with_eHealth') }}
            </button>
        </div>

        <x-slot name="navigation">
            <div class="flex flex-col -my-4">
                <form wire:submit.prevent="applyFilters">
                    <div >
                        <div class="form-row-4">
                            <div class="form-group group">
                                <input type="text"
                                       id="employee_search_role"
                                       placeholder=" "
                                       class="input peer pl-8"
                                       wire:model.defer="search"
                                       autocomplete="off" />
                                <label for="employee_search_role" class="label pl-8">Пошук за ПІБ працівника</label>
                                @icon('search', 'w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none')
                                <button wire:click="clearSearch" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    @icon('x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        </div>

                        <div class="form-row-4">
                        <div class="form-group group">
                                <select wire:model.defer="filter.service_type"
                                        id="filter_service_type"
                                        class="input peer"
                                >
                                    <option value="">Виберіть вид послуги</option>
                                    {{-- @foreach($service_types as $type)
                                        <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                    @endforeach --}}
                                </select>
                                <label for="filter_service_type" class="label">Вид послуги</label>
                            </div>
                        </div>

                        <div class="form-row-4">
                            <div class="form-group group">
                                <label for="statusFilter" class="label">Статус</label>
                                <div class="relative" x-data="{ open: false, selectedStatuses: @entangle('status') }">
                                    <input type="text"
                                           id="statusFilter"
                                           class="input peer"
                                           placeholder="Активні, Не активний"
                                           x-on:click="open = !open"
                                           :value="selectedStatuses.length ? selectedStatuses.map(s => {
                                                if (s === 'ACTIVE') return 'Активний';
                                                if (s === 'INACTIVE') return 'Не активний';
                                                return s;
                                            }).join(', ') : 'Активні, Не активний'"
                                           readonly
                                    />
                                    <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <div x-show="open"
                                         x-on:click.away="open = false"
                                         x-transition
                                         class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg">
                                        <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="ACTIVE" wire:model.defer="status"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('forms.active') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="INACTIVE" wire:model.defer="status"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('forms.non_active') }}</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                        <div class="mb-9 mt-6 flex flex-col sm:flex-row gap-2 w-full">
                            <button type="submit" class="flex items-center gap-2 button-primary">
                                @icon('search', 'w-4 h-4')
                                <span>{{ __('forms.search') }}</span>
                            </button>

                            <button type="button" wire:click="resetFilters" class="button-primary-outline-red">
                                {{ __('forms.reset_all_filters') }}
                            </button>
                        </div>

                </form>
            </div>
        </x-slot>

    </x-header-navigation>

    <div class="flow-root mt-8 shift-content pl-3.5">
        <div class="max-w-screen-xl">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table
                    class="w-full min-w-[1100px] table-fixed text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 w-[25%]">{{ __('employee-role.employee_fio') }}</th>
                        <th class="px-6 py-3 w-[15%]">{{ __('employee-role.service_type') }}</th>
                        <th class="px-6 py-3 w-[20%]">{{ __('forms.divisions') }}</th>
                        <th class="px-6 py-3 w-[15%]">{{ __('employee-role.service_condition') }}</th>
                        <th class="px-6 py-3 w-[15%]">{{ __('employee-role.status') }}</th>
                        <th class="px-6 py-3 w-[10%] text-center">{{ __('forms.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    <tr {{-- wire:key='{{ $role->id }}' --}}
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200"
                    >
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white align-top"
                        >
                            {{-- {{ $role->employee->fio ?? }} --}}
                            Шевченко Тарас Григорович
                        </th>
                        <td class="px-6 py-4 align-top">
                            {{-- {{ $role->service_type_name ?? }} --}}
                            Терапевт
                        </td>
                        <td class="px-6 py-4 break-words whitespace-normal align-top">
                            {{-- {{ $role->division->name ?? }} --}}
                            Амбулаторія №1
                        </td>
                        <td class="px-6 py-4 align-top">
                            {{-- {{ $role->service_condition_name ??}} --}}
                            Стаціонарні
                        </td>
                        <td class="px-6 py-4 align-top">
                            {{-- @if (true) --}}
                            <span class="badge-green">{{ __('forms.status.active') }}</span>
                            {{-- @else
                                <span class="badge-red">{{ __('forms.status.non_active') }}</span>
                            @endif --}}
                        </td>
                        <td class="px-14 py-4 text-center align-top">
                            <a href="#"
                               class="flex items-center gap-2 w-full px-4 py-2.5 text-left text-sm text-gray-600 hover:bg-gray-50"
                               title="{{ __('forms.edit') }}">
                                @icon('edit', 'w-5 h-5 text-gray-600')
                            </a>
                        </td>
                    </tr>
                    {{-- @empty --}}
                    {{-- @endforelse --}}
                    </tbody>
                </table>
            </div>

            <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
                  {{--{{ ->links() }}--}}
            </div>
    </div>

</div>
</div>
