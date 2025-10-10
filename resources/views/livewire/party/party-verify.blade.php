<div>
    {{-- Breadcrumb Navigation --}}
    <nav class="flex mb-4" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 rtl:space-x-reverse">
            <li class="inline-flex items-center">
                <a href="{{ route('home.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-4 h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                    </svg>
                    @lang('general.home')
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <a href="{{ route('employee.index', ['legalEntity' => $legalEntity->id]) }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">@lang('forms.employees')</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-3 h-3 text-gray-400 mx-1 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">@lang('general.verification')</span>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Page Title --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            @lang('employees.verification_title')
        </h1>
        <p class="mt-1 text-lg text-gray-600 dark:text-gray-300">
            {{ $party->fullName }}
        </p>
    </div>

    <x-section class="mt-8">
        <h2 class="text-xl font-bold mb-6 text-gray-800 dark:text-white">@lang('general.verification')</h2>
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden border border-gray-200 dark:border-gray-700 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 w-1/5">@lang('general.verification')</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">@lang('forms.status.label')</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">@lang('forms.reason_code')</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 w-2/5">@lang('forms.ehealth_comment_recommendation')</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-600">
                        @forelse($verificationDetails['details'] ?? [] as $key => $details)
                            @php
                                $status = data_get($details, 'verification_status');
                                $reason = data_get($details, 'verification_reason');
                                $comment = data_get($details, 'verification_comment');
                                $result = data_get($details, 'result');
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white align-top">
                                    @lang('general.verification_types.' . $key)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm align-top">
                                    @if($status === 'VERIFIED')
                                        <span class="badge-green">@lang('general.verified')</span>
                                    @elseif($status)
                                        <span class="badge-red">@lang('general.' . strtolower($status))</span>
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 align-top">
                                    <div>{{ $reason ?? '-' }}</div>
                                    @if($result)
                                        <div class="text-xs text-gray-400">(@lang('forms.code'): {{ $result }})</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-top">
                                    @if(!empty($comment))
                                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $comment }}</span>
                                    @elseif ($status !== 'VERIFIED')
                                        @lang('general.recommendations.' . $key, ['result' => $result])
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    @lang('forms.verification_details_not_loaded')
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="p-4 mt-6 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
            @lang('general.ehealth_fitness_warning', ['status' => '"Потрібна верифікація"'])
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-start gap-4 mt-8">
            <a href="{{ route('employee.index', ['legalEntity' => $legalEntity->id]) }}" class="button-secondary">@lang('forms.back')</a>
            @php
                $employeeToEdit = $party->employees->first();
            @endphp
            @if($employeeToEdit)
                <a href="{{ route('employee.edit', ['legalEntity' => $legalEntity->id, 'employee' => $employeeToEdit->id]) }}" class="button-primary">
                    @lang('forms.edit_personal_data')
                </a>
            @endif
            @php
                $dracsDeathStatus = data_get($verificationDetails, 'details.dracs_death.verification_status');
            @endphp
            @if($dracsDeathStatus === 'NOT_VERIFIED')
                <button type="button" wire:click="openUpdateModal('dracs_death')" class="button-primary-outline">@lang('forms.update_death_data')</button>
            @else
                <div class="flex flex-col">
                    <button type="button" class="button-disabled" disabled>@lang('forms.update_death_data')</button>
                    <p class="text-xs text-gray-500 mt-1">@lang('general.update_unavailable_reason')</p>
                </div>
            @endif
        </div>
    </x-section>

    {{-- Update Status Modal --}}
    @if ($showUpdateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 dark:bg-opacity-80" x-data @keydown.escape.window="$wire.closeModal()">
            <div class="relative w-full max-w-2xl m-4 bg-white rounded-lg shadow dark:bg-gray-800" @click.away="$wire.closeModal()">
                <form wire:submit.prevent="updateStatus">
                    {{-- Modal header --}}
                    <div class="flex items-center justify-between p-4 border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Оновити статус верифікації співробітника в ДРАЦС
                        </h3>
                        <button type="button" @click="$wire.closeModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        </button>
                    </div>
                    {{-- Modal body (form) --}}
                    <div class="p-6 space-y-6">
                        <div class="form-group group">
                            <select wire:model.defer="status" id="status" class="input peer text-gray-500 dark:text-gray-400">
                                <option value="">Оберіть статус</option>
                                <option value="VERIFIED">Верифіковано</option>
                                <option value="NOT_VERIFIED">Не верифіковано</option>
                            </select>
                            <label for="status" class="label">Статус</label>
                            @error('status') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group group">
                            <select wire:model.defer="reason" id="reason" class="input peer text-gray-500 dark:text-gray-400">
                                <option value="">Оберіть причину</option>
                                <option value="MANUAL_CONFIRMED">Підтверджено вручну</option>
                                <option value="MANUAL_NOT_CONFIRMED">Не підтверджено вручну</option>
                            </select>
                            <label for="reason" class="label">Причина верифікації</label>
                            @error('reason') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group group">
                            <textarea wire:model.defer="comment" id="comment" rows="4" class="input peer" placeholder=" "></textarea>
                            <label for="comment" class="label">Коментар</label>
                            @error('comment') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    {{-- Modal footer (buttons) --}}
                    <div class="flex items-center justify-end p-6 space-x-2 border-t border-gray-200 rounded-b dark:border-gray-600">
                        <button type="button" @click="$wire.closeModal()" class="button-secondary">Скасувати</button>
                        <button type="submit" class="button-primary">Оновити дані в ЕСОЗ</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
