@php
    use App\Models\Contracts\ContractRequest;
    use App\Models\LegalEntity;
    use Carbon\Carbon;

    $route = '#';
    if (legalEntity()->type->name === LegalEntity::TYPE_PHARMACY) {
        $route = route('contract-request.reimbursement.create', legalEntity());
    } elseif (legalEntity()->type->name === LegalEntity::TYPE_MSP) {
        $route = route('contract-request.capitation.create', legalEntity());
    }
@endphp

<div>
    <livewire:components.x-message :key="time()"/>
    <x-forms.loading/>

    <x-header-navigation class="items-start">
        <x-slot name="title">{{ __('Заявки на договори') }}</x-slot>

        <div class="mt-3 ml-0 flex flex-col sm:flex-row sm:flex-wrap gap-2 self-start">
            @can('create', ContractRequest::class)
                <a href="{{ $route }}" class="button-primary flex items-center gap-2" wire:navigate>
                    @icon('plus', 'w-4 h-4')
                    {{ __('contracts.new') }}
                </a>
            @endcan

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
                            <th class="index-table-th w-[10%]">{{ __('contracts.date_added') }}</th>
                            <th class="index-table-th w-[15%]"></th>
                        </tr>
                        </thead>
                        <tbody class="index-table-tbody">
                        @foreach($contracts as $item)
                            <tr>
                                <td class="index-table-td">
                                    <div class="text-sm text-gray-900 font-medium">
                                        {{ $item->contract_number ?? __('forms.draft') }}
                                    </div>
                                    @if($item->contract_number)
                                        <div class="text-xs text-gray-500 mt-0.5">ID: {{ $item->id }}</div>
                                    @endif
                                </td>
                                <td class="index-table-td">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $item->type ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="index-table-td">
                                    @if(is_object($item->status) && method_exists($item->status, 'label'))
                                        <x-status-badge :status="$item->status"/>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $item->status }}
                                        </span>
                                    @endif

                                    @if($item->status_reason)
                                        <div class="text-xs text-red-500 mt-1 max-w-xs truncate" title="{{ $item->status_reason }}">
                                            {{ $item->status_reason }}
                                        </div>
                                    @endif
                                </td>
                                <td class="index-table-td text-sm text-gray-500">
                                    {{ $item->start_date ? Carbon::parse($item->start_date)->format('d.m.Y') : '...' }}
                                    -
                                    {{ $item->end_date ? Carbon::parse($item->end_date)->format('d.m.Y') : '...' }}
                                </td>
                                <td class="index-table-td text-sm text-gray-500">
                                    {{ $item->created_at ? Carbon::parse($item->created_at)->format('d.m.Y H:i') : '-' }}
                                </td>
                                <td class="index-table-td-actions text-right flex justify-end gap-2 items-center pr-4">

                                    {{-- EDIT BUTTON (Only for new applications) --}}
                                    {{-- Check the status with a string or Enum--}}
                                    @if($item->status === 'NEW' || (is_object($item->status) && $item->status->value === 'NEW'))
                                        <a href="{{ route('contract-request.edit', ['legalEntity' => legalEntity()->id, 'contract' => $item->uuid]) }}"
                                           class="text-gray-400 hover:text-blue-600 transition-colors"
                                           wire:navigate
                                           title="{{ __('Редагувати') }}"
                                        >
                                            @icon('pencil', 'w-5 h-5')
                                        </a>
                                    @endif

                                    {{-- VIEW BUTTON --}}
                                    <a href="{{ route('contract-request.show', ['legalEntity' => legalEntity()->id, 'contract' => $item->uuid]) }}"
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
                                    {{ __('contracts.contracts_request_not_found') }}
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
