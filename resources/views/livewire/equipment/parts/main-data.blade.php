@use('App\Enums\Equipment\{Status, Type}')

<fieldset class="fieldset form shift-content">
    <legend class="legend">
        {{ __('forms.main_information') }}
    </legend>

    <div class="form-row-2">
        <div class="form-group group">
            <input wire:model="form.names.0.name"
                   type="text"
                   name="equipmentName"
                   id="equipmentName"
                   placeholder=" "
                   required
                   class="peer input"
            >
            <label for="equipmentName" class="label">
                {{ __('equipments.name_medical_product') }}
            </label>

            @error('form.names.*.name')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group group">
            <select wire:model="form.names.0.type"
                    name="typeName"
                    id="typeName"
                    required
                    class="peer input-select"
            >
                <option value="">{{ __('forms.select') }}</option>
                @foreach(Type::options() as $key => $nameType)
                    <option value="{{ $key }}">{{ $nameType }}</option>
                @endforeach
            </select>
            <label for="typeName" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                {{ __('equipments.name_type') }}
            </label>

            @error('form.names.*.type')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group group">
            <select wire:model="form.type"
                    name="typeMedicalDevice"
                    id="typeMedicalDevice"
                    required
                    class="peer input-select"
            >
                <option value="" selected>{{ __('forms.select') }}</option>
                @foreach(dictionary()->getDictionary('device_definition_classification_type') as $key => $type)
                    <option value="{{ $key }}">{{ $type }}</option>
                @endforeach
            </select>
            <label for="typeMedicalDevice" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                {{ __('equipments.type_medical_device') }}
            </label>

            @error('form.type')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group group">
            <input wire:model="form.serialNumber"
                   type="text"
                   name="serialNumber"
                   id="serialNumber"
                   placeholder=" "
                   class="peer input"
            >
            <label for="serialNumber" class="label">
                {{ __('equipments.serial_number') }}
            </label>

            @error('form.serialNumber')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group group">
            <input value="{{ Status::from($form->status)->label() }}"
                   type="text"
                   name="status"
                   id="status"
                   placeholder=" "
                   class="peer input"
                   disabled
                   readonly
            >
            <label for="status" class="label">
                {{ __('forms.status.label') }}
            </label>

            @error('form.status')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group group">
            <input value="{{ $recorderFullName }}"
                   type="text"
                   name="recorder"
                   id="recorder"
                   placeholder=" "
                   class="peer input"
                   disabled
            >
            <label for="recorder" class="label">
                {{ __('equipments.recorder') }}
            </label>

            @error('form.recorder')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</fieldset>
