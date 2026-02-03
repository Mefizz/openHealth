@php
    use App\Models\Contracts\ContractRequest;
@endphp

<div>
    <livewire:components.x-message :key="time()"/>
    <x-forms.loading/>

    <x-header-navigation class="items-start">
        <x-slot name="title">{{ __('forms.contracts') }}</x-slot>

        <div class="mt-3 ml-0 flex flex-col sm:flex-row sm:flex-wrap gap-2 self-start">
            @can('sync', ContractRequest::class)
                <button wire:click="sync" type="button" class="button-sync flex items-center gap-2 whitespace-nowrap">
                    @icon('refresh', 'w-4 h-4')
                    {{ __('forms.synchronise_with_eHealth') }}
                </button>
            @endcan
        </div>
    </x-header-navigation>

    <div class="flow-root mt-8 shift-content pl-3.5">
        <div class="max-w-screen-xl">
            @if ($contracts->isNotEmpty())
                <div class="index-table-wrapper">
                    <table class="index-table">
                        <thead class="index-table-thead">
                        <tr>
                            <th class="index-table-th w-[25%]">{{ __('contracts.number_label') }}</th>
                            <th class="index-table-th w-[15%]">{{ __('contracts.type_label') }}</th>
                            <th class="index-table-th w-[15%]">{{ __('contracts.status_label') }}</th>
                            <th class="index-table-th w-[20%]">{{ __('contracts.period') }}</th>
                            <th class="index-table-th w-[15%]">{{ __('contracts.date_added') }}</th>
                            <th class="index-table-th w-[10%]"></th>
                        </tr>
                        </thead>
                        <tbody class="index-table-tbody">
                        @foreach($contracts as $item)
                            <tr>
                                <td class="index-table-td">
                                    <div class="text-sm text-gray-900 font-medium">
                                        {{ $item->contract_number }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ $item->contractor_legal_entity_id }}
                                    </div>
                                </td>
                                <td class="index-table-td">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $item->type }}
                                    </span>
                                </td>
                                <td class="index-table-td">
                                    <x-status-badge :status="$item->status"/>
                                </td>
                                <td class="index-table-td text-sm text-gray-500">
                                    {{ $item->start_date?->format('d.m.Y') }} - {{ $item->end_date?->format('d.m.Y') }}
                                </td>
                                <td class="index-table-td text-sm text-gray-500">
                                    {{ $item->start_date?->format('d.m.Y') ?? $item->created_at?->format('d.m.Y') }}
                                </td>
                                <td class="index-table-td-actions text-right">
                                    <a href="{{ route('contract.show', [legalEntity(), $item->uuid]) }}"
                                       class="text-gray-400 hover:text-blue-600 transition-colors"
                                       wire:navigate
                                       title="{{ __('Перегляд') }}"
                                    >
                                        @icon('eye', 'w-5 h-5')
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <fieldset class="fieldset !mx-auto mt-8 shift-content">
                    <legend class="legend relative -top-5">@icon('nothing-found', 'w-28 h-28')</legend>
                    <div class="p-4 rounded-lg bg-blue-100 flex items-start mb-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @icon('alert-circle', 'w-5 h-5 text-blue-500 mr-3 mt-1')
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-blue-800">
                                    {{ __('forms.nothing_found') }}
                                </p>
                                <p class="text-sm text-blue-600">
                                    {{ __('forms.changing_search_parameters') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            @endif

            <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
                {{ $contracts->links() }}
            </div>
        </div>
    </div>
</div>
