<section class="section-form">
    <x-header-navigation x-data="{ showFilter: false }" class=''>
        <x-slot name="title">{{ __('equipment.new_equipment') }}</x-slot>
    </x-header-navigation>

    <div class="form">

    <fieldset class="fieldset form shift-content">
        <legend class="legend">
            {{ __('forms.main_information') }}
        </legend>
        <div class="form-row-3">
            <div class="form-group group">
                <input  wire:model="form.equipmentName"
                        type="text"
                        name="equipmentName"
                        id="equipmentName"
                        placeholder=" "
                        required
                        class="peer input"
                >
                <label for="equipmentName" class="label">
                    {{ __('equipment.name_medical_product') }}
                </label>
                @error('form.equipmentName')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group group">
                <select name="typeName"
                        id="typeName"
                        required
                        class="peer input-select"
                        wire:model="form.typeId"
                >
                    <option value="">{{ __('forms.select') }}</option>
                </select>
                <label for="typeName" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                    {{ __('equipment.name_type') }}
                </label>
                @error('form.typeId')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group group">
                <select wire:model="form.typeMedicalDevice"
                        name="typeMedicalDevice"
                        id="typeMedicalDevice"
                        required
                        class="peer input-select"
                >
                    <option value="">{{ __('forms.select') }}</option>
                </select>
                <label for="typeMedicalDevice" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                    {{ __('equipment.type_medical_device') }}
                </label>
                @error('form.typeMedicalDevice')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group group">
                <input  wire:model="form.serialNumber"
                        type="number"
                        name="serialNumber"
                        id="serialNumber"
                        placeholder=" "
                        required
                        class="peer input"
                >
                <label for="serialNumber" class="label">
                    {{ __('equipment.serial_number') }}
                </label>
                @error('form.serialNumber')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group group">
                <input  wire:model="form.status"
                        type="text"
                        name="status"
                        id="status"
                        placeholder=" "
                        class="peer input"
                >
                <label for="status" class="label">
                    {{ __('equipment.status') }}
                </label>
                @error('form.status')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-group group">
                <input  wire:model="form.dataEntryEmployee"
                        type="text"
                        name="dataEntryEmployee"
                        id="dataEntryEmployee"
                        placeholder=" "
                        class="peer input"
                >
                <label for="dataEntryEmployee" class="label">
                    {{ __('equipment.data_entry_employee') }}
                </label>
                @error('form.dataEntryEmployee')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </fieldset>


        <fieldset class="fieldset form shift-content">
            <legend class="legend">
                {{ __('equipment.additional_data') }}
            </legend>
            <div class="form-row-3">
            <div class="form-group group">
                <select wire:model="form.medicalFacility"
                        name="medicalFacility"
                        id="medicalFacility"
                        class="peer input-select"
                >
                    <option value="">{{ __('forms.select') }}</option>
                </select>
                <label for="medicalFacility" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                    {{ __('equipment.medical_facility') }}
                </label>
                @error('form.medicalFacility')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
                <div class="form-group group">
                    <select wire:model="form.accessibility"
                            name="accessibility"
                            id="accessibility"
                            class="peer input-select"
                    >
                        <option value="">{{ __('forms.select') }}</option>
                    </select>
                    <label for="accessibility" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                        {{ __('equipment.accessibility') }}
                    </label>
                    @error('form.accessibility')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="form-row-3">
                <div class="form-group group">
                    <input  wire:model="form.inventoryNumber"
                            type="number"
                            name="inventoryNumber"
                            id="inventoryNumber"
                            placeholder=" "
                            class="peer input"
                    >
                    <label for="inventoryNumber" class="label">
                        {{ __('equipment.inventory_number') }}
                    </label>
                    @error('form.inventoryNumber')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group group">
                    <input  wire:model="form.producer"
                            type="text"
                            name="producer"
                            id="producer"
                            placeholder=" "
                            class="peer input"
                    >
                    <label for="producer" class="label">
                        {{ __('equipment.producer') }}
                    </label>
                    @error('form.producer')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="form-row-3">
                <div class="form-group datepicker-wrapper relative w-full">
                    <input
                        wire:model="form.productionDate"
                        type="text"
                        name="productionDate"
                        id="productionDate"
                        class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400"
                        placeholder=" "
                        required
                        datepicker-autohide d
                        atepicker-format="yyyy-mm-dd"
                        datepicker-button="false"
                    >
                    <label for="productionDate" class="wrapped-label">{{__('equipment.production_date')}}</label>
                    @error('form.productionDate') <p class="text-error">{{$message}}</p> @enderror
                </div>
                <div class="form-group datepicker-wrapper relative w-full">
                    <input
                        wire:model="form.expirationDate"
                        type="text"
                        name="expirationDate"
                        id="expirationDate"
                        class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400"
                        placeholder=" "
                        required
                        datepicker-autohide d
                        atepicker-format="yyyy-mm-dd"
                        datepicker-button="false"
                    >
                    <label for="expirationDate" class="wrapped-label">{{__('equipment.expiration_date')}}</label>
                    @error('form.expirationDate')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="form-row-3">
                <div class="form-group group">
                    <input  wire:model="form.modelNumber"
                            type="number"
                            name="modelNumber"
                            id="modelNumber"
                            placeholder=" "
                            class="peer input"
                    >
                    <label for="modelNumber" class="label">
                        {{ __('equipment.model_number') }}
                    </label>
                    @error('form.modelNumber')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group group">
                    <input  wire:model="form.procurementNumber"
                            type="number"
                            name="procurementNumber"
                            id="procurementNumber"
                            placeholder=" "
                            class="peer input"
                    >
                    <label for="procurementNumber" class="label">
                        {{ __('equipment.procurement_number') }}
                    </label>
                    @error('form.procurementNumber')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label for="notesComments"
                           class="peer appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ __('equipment.notes_and_comments') }}
                    </label>
                    <textarea
                        id="notesComments"
                        wire:model="form.notesComments"
                        class="textarea !text-gray-500 dark:!text-gray-400 mt-1"
                        placeholder="{{ __('equipment.write_comment') }}">
                        </textarea>
                    @error('form.notesComments') <p class="text-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <div class="mt-6 flex flex-row items-center gap-4 border-t border-gray-200 pt-6">
            <div class="flex items-center space-x-3">
                <a href="" class="button-minor">
                    {{__('forms.cancel')}}
                </a>
                <button
                    type="submit"
                    class="button-primary-outline flex items-center gap-2 px-4 py-2"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <svg
                        class="w-5 h-5"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linejoin="round" d="M10 12v1h4v-1m4 7H6a1 1 0 0 1-1-1V9h14v9a1 1 0 0 1-1 1ZM4 5h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">{{ __('forms.save') }}</span>
                    <span wire:loading wire:target="save">{{ __('forms.saving') }}...</span>
                </button>
                <button type="button" wire:click="create" class="button-primary">
                    {{ __('forms.create') }}
                </button>
            </div>
        </div>

    </div>
</section>
