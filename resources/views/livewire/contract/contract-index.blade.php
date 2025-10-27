<div>
    <x-section-navigation x-data="{ showFilter: false }">
        <x-slot name="title">
            {{ __('forms.contract.contracts') }}
        </x-slot>
    </x-section-navigation>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-18 shift-content pl-3.5">
        <div class="w-96">
            <x-forms.form-group>
                <x-slot name="label">
                    <label for="contract_type_filter" class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
                        <span>{{ __('forms.contract.show') }}</span>
                    </label>
                </x-slot>
                <x-slot name="input">
                    <div class="form-group group w-full relative" x-data="{ open: false, selectedType: @entangle('contractType').live }">
                        <input type="text"
                               id="contract_type_filter"
                               class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                               placeholder="Оберіть тип"
                               x-on:click="open = !open"
                               :value="selectedType === 'CONTRACTS' ? 'Договори' : (selectedType === 'APPLICATIONS' ? 'Заявки на договір' : 'Оберіть тип')"
                               readonly
                               autocomplete="off"
                        />
                        <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none z-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"></path>
                        </svg>
                        <div x-show="open"
                             x-on:click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute z-20 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg top-full"
                        >
                            <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox"
                                               value="APPLICATIONS"
                                               name="contract_type_select"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                        />
                                        <span class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Заявки на договір</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox"
                                               value="CONTRACTS"
                                               name="contract_type_select"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                        />
                                        <span class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Договори</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                </x-slot>
            </x-forms.form-group>
        </div>
        <div class="flex items-center space-x-2 mt-8">
            <a
                href="{{ route('contract.create', [legalEntity()]) }}"
                type='button'
                class='text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800'>
                {{ __('forms.contract.new_contract') }}
            </a>
            <button {{--wire:click="sync"--}} type="button" class="button-sync">
                {{ __('forms.synchronise_with-eHealth') }}
            </button>
        </div>
    </div>
    <div class="flow-root mt-8 pl-3.5 shift-content">
        <div class="max-w-screen-xl">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table
                    class="w-full min-w-[900px] table-fixed text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-[28%]">{{ __('forms.contract.number') }}</th>
                        <th scope="col" class="px-6 py-3 w-[22%]">{{ __('forms.contract.startDateContract') }}</th>
                        <th scope="col" class="px-6 py-3 w-[20%]">{{ __('forms.contract.endDateContract') }}</th>
                        <th scope="col" class="px-6 py-3 w-[15%]">{{ __('forms.contract.status') }}</th>
                        <th scope="col" class="px-6 py-3 w-[15%] text-center">{{ __('forms.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($contracts as $contract)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4 break-words whitespace-normal align-top font-medium text-gray-900 dark:text-white">
                                <p>{{ $contract->contract_number ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 break-words whitespace-normal align-top text-gray-900 dark:text-white">
                                <p>{{ $contract->start_date ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 break-words whitespace-normal align-top text-gray-900 dark:text-white">
                                <p>{{ $contract->end_date ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                @if ($contract->status === 'DRAFT')
                                    <span class="badge-red">{{ $contract->status }}</span>
                                @elseif ($contract->status === 'TERMINATED')
                                    <span class="badge-red">{{ $contract->status }}</span>
                                @elseif ($contract->status === 'PENDING_APPROVAL' || $contract->status === 'UNSIGNED' || $contract->status === 'UNSYNCED')
                                    <span class="badge-yellow">{{ $contract->status }}</span>
                                @else
                                    <span class="badge-green">{{ $contract->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @include('livewire.contract.actions', ['contract' => $contract])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-black w-full p-4 border-gray-200 text-center dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                colspan="5"
                            >
                                <p>{{ __('Нічого не знайдено') }}</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($contracts->hasPages())
                <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
                    {{ $contracts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
