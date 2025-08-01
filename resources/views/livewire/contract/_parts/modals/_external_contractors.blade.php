<div
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
    x-data @keydown.escape.window="$wire.closeModal()"
>
    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl dark:bg-gray-800" @click.away="$wire.closeModal()">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                Нова залучена особа (підрядник)
            </h3>
            <button type="button" @click="$wire.closeModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                <span class="sr-only">Close modal</span>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-4 md:p-5 space-y-6">
            {{-- EDRPOU --}}
            <div class="form-group">
                <input type="text"
                       name="edrpou"
                       id="edrpou"
                       class="input peer"
                       placeholder=" "
                       wire:model.defer="form.external_contractors.edrpou"
                />
                <label for="edrpou" class="label">{{ __('ЄДРПОУ') }} *</label>
                @error('form.external_contractors.edrpou') <p class="text-error">{{ $message }}</p> @enderror
                @error('form.external_contractors.legal_entity_id') <p class="text-error">{{ $message }}</p> @enderror
            </div>

            {{-- Contract Number --}}
            <div class="form-group">
                <input type="text"
                       name="ext_contract_number"
                       id="ext_contract_number"
                       class="input peer"
                       placeholder=" "
                       wire:model.defer="form.external_contractors.contract.number"
                />
                <label for="ext_contract_number" class="label">{{ __('forms.contract.external_contractors_number') }} *</label>
                @error('form.external_contractors.contract.number') <p class="text-error">{{ $message }}</p> @enderror
            </div>

            {{-- Dates (in 2 columns) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group datepicker-wrapper relative">
                    <input wire:model.defer="form.external_contractors.contract.issued_at"
                           type="text"
                           name="ext_issued_at"
                           id="ext_issued_at"
                           class="peer input pl-10 appearance-none datepicker-input"
                           placeholder=" "
                           datepicker-autohide
                           datepicker-format="yyyy-mm-dd"
                           datepicker-button="false"
                    />
                    <label for="ext_issued_at" class="wrapped-label">{{__('forms.contract.external_contractors_issued_at')}}</label>
                    @error('form.external_contractors.contract.issued_at') <p class="text-error">{{$message}}</p> @enderror
                </div>
                <div class="form-group datepicker-wrapper relative">
                    <input wire:model.defer="form.external_contractors.contract.expires_at"
                           type="text"
                           name="ext_expires_at"
                           id="ext_expires_at"
                           class="peer input pl-10 appearance-none datepicker-input"
                           placeholder=" "
                           datepicker-autohide
                           datepicker-format="yyyy-mm-dd"
                           datepicker-button="false"
                    />
                    <label for="ext_expires_at" class="wrapped-label">{{__('forms.contract.external_contractors_expires_at')}}</label>
                    @error('form.external_contractors.contract.expires_at') <p class="text-error">{{$message}}</p> @enderror
                </div>
            </div>

            {{-- Divisions and Services (in 2 columns) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <select id="ext_division_id"
                            class="input-select"
                            wire:model="form.external_contractors.divisions.id">
                        <option value="" disabled>{{__('Підрозділ')}} *</option>
                        {{-- Assuming $divisions is available from the parent component --}}
                        @foreach($divisions as $division)
                            <option value="{{$division->uuid}}">{{$division->name}}</option>
                        @endforeach
                    </select>
                    <label for="ext_division_id" class="label">{{__('Підрозділ')}} *</label>
                    @error('form.external_contractors.divisions.id') <p class="text-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <select id="ext_medical_service"
                            class="input-select"
                            wire:model="form.external_contractors.divisions.medical_service">
                        <option value="" disabled>{{__('Медична послуга')}} *</option>
                        {{-- Assuming you have a list of services to loop through --}}
                        <option value="primary_care">Послуга ПМД</option>
                    </select>
                    <label for="ext_medical_service" class="label">{{__('Медична послуга')}} *</label>
                    @error('form.external_contractors.divisions.medical_service') <p class="text-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="flex items-center justify-end p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600 space-x-4">
            <button @click="$wire.closeModal()" type="button" class="button-secondary">Скасувати</button>
            <button wire:click.prevent="addExternalContractors()" type="button" class="button-primary">Додати</button>
        </div>
    </div>
</div>
