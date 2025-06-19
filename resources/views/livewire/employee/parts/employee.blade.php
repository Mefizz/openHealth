<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{__('forms.personalData')}}</h2>
    </legend>

    <div class="form-row-3">
        <div class="form-group group relative">
            <input
                wire:model="form.party.lastName"
                type="text"
                name="lastName"
                id="lastName"
                class="input peer @error('form.party.lastName') input-error @enderror"
                placeholder=" "
                required
            />

            <label for="lastName" class="label">
                {{__('forms.last_name')}}
            </label>

            @error('form.party.lastName')
            <p class="text-error">
                {{$message}}
            </p>
            @enderror
        </div>

        <div class="form-group group">
            <input wire:model="form.party.firstName"
                   type="text"
                   name="firstName"
                   id="firstName"
                   class="input peer @error('form.party.firstName') input-error @enderror"
                   placeholder=" "
                   required
            />
            <label for="firstName" class="label">
                {{__('forms.first_name')}}
            </label>

            @error('form.party.firstName')
            <p class="text-error">
                {{$message}}
            </p>
            @enderror
        </div>

        <div class="form-group group">
            <input
                wire:model="form.party.secondName"
                type="text"
                name="secondName"
                id="secondName"
                class="input peer @error('form.party.secondName') input-error @enderror"
                placeholder=" "
            />
            <label for="secondName" class="label">
                {{__('forms.second_name')}}
            </label>

            @error('form.party.secondName')
            <p class="text-error">
                {{$message}}
            </p>
            @enderror
        </div>
    </div>

    <div class="form-row-4">
        <div class="form-group group">
            <label for="employeeGender" class="sr-only">{{__('forms.select')}} {{__('forms.gender')}}</label>
            <select wire:model="form.party.gender"
                    id="employeeGender"
                    class="input-select peer @error('form.party.gender') input-error @enderror"
                    required
            >
                <option selected>{{__('forms.gender')}} *</option>
                @foreach($this->dictionaries['GENDER'] as $k=>$gender )
                    <option value="{{$k}}">{{$gender}}</option>
                @endforeach
            </select>

            @error('form.party.gender')
            <p class="text-error">
                {{$message}}
            </p>
            @enderror
        </div>

        <div class="form-group group">

            <input wire:model="form.party.birthDate"
                   datepicker-max-date="{{ now()->format('Y-m-d') }}"
                   type="text"
                   name="birthDate"
                   id="birthDate"
                   class="input datepicker-input peer @error('form.party.birthDate') input-error @enderror"
                   placeholder=" "
                   required
            />

            <label for="birthDate" class="label">
                {{__('forms.birth_date')}}
            </label>

            @error('form.party.birthDate')
            <p class="text-error">
                {{$message}}
            </p>
            @enderror
        </div>


        <div class="form-group group">

            <input wire:model="form.party.email"
                   type="text"
                   name="email"
                   id="email"
                   class="input peer disabled:bg-gray-100 disabled:cursor-not-allowed @error('form.party.email') input-error @enderror"
                   placeholder=" "
                   required
                   @if(isset($this->employeeId) && $this->employeeId) disabled @endif
            />
            <label for="email" class="label">
                {{__('forms.email')}}
            </label>
            @error('form.party.email')
            <p class="text-error">{{$message}}</p>
            @enderror
        </div>

        <div class="form-group group">
            <input wire:model="form.party.taxId"
                   type="text"
                   id="taxId"
                   name="taxId"
                   class="input peer disabled:bg-gray-100 disabled:cursor-not-allowed @error('form.party.taxId') input-error @enderror"
                   placeholder=" "
                   required
                   @if(isset($this->employeeId) && $this->employeeId) disabled @endif
            />
            <label for="taxId" class="label">
                {{ __('forms.tax_id') }}
            </label>
            @error('form.party.taxId')
            <p class="text-error">{{$message}}</p>
            @enderror
        </div>
    </div>

    <div class="form-row-4" x-data="{
        employeePosition: $wire.entangle('form.position'),
        employeeType: $wire.entangle('form.employeeType'),
        employeeTypePosition: $wire.employeeTypePosition,
        availablePositions: null
    }">

        <div class="form-group group">
            <label for="employeeType" class="sr-only">{{__('forms.roleChoose')}}</label>
            <select x-model="employeeType"
                    wire:model="form.party.employeeType"
                    id="employeeType"
                    class="input-select peer @error('form.employeeType') input-error @enderror" {{-- ЗМІНЕНО: @error --}}
                    required
            >
                <option selected>{{__('forms.roleChoose')}} *</option>
                @foreach($this->dictionaries['EMPLOYEE_TYPE'] as $k=>$employeeTypeOption) {{-- Змінено ім'я змінної циклу --}}
                <option value="{{$k}}">{{$employeeTypeOption}}</option>
                @endforeach
            </select>

            @error('form.employeeType') {{-- ЗМІНЕНО: @error --}}
            <p class="text-error">
                {{$message}}
            </p>
            @enderror
        </div>

        <div class="form-group group">
            <label for="position" class="sr-only">{{__('forms.select_position')}}</label>
            <select x-model="employeePosition"
                    id="position"
                    class="input-select peer @error('form.position') input-error @enderror" {{-- ЗМІНЕНО: @error --}}
                    required
            >
                <option selected>{{__('forms.select_position')}} *</option>
                {{-- Only show positions associated with certain employee types --}}
                <template x-for="(positionOption, index) in employeeTypePosition[employeeType]" :key="index"> {{-- Змінено ім'я змінної циклу --}}
                    <option :value="index" x-text="positionOption"></option>
                </template>

            </select>
        </div>

        <div class="form-group group">

            <input wire:model="form.startDate" {{-- ЗМІНЕНО: прив'язка до кореневого startDate --}}
            datepicker-max-date="{{ now()->format('Y-m-d') }}"
                   type="text"
                   name="startDate"
                   id="startDate"
                   class="input datepicker-input peer @error('form.startDate') input-error @enderror" {{-- ЗМІНЕНО: @error --}}
                   placeholder=" "
                   required
            />

            <label for="startDate" class="label">
                {{__('forms.startDateWork')}}
            </label>

            @error('form.startDate')
            <p class="text-error">
                {{$message}}
            </p>
            @enderror
        </div>

        <div class="form-group group">
            <input wire:model="form.party.workingExperience"
                   type="number"
                   id="workingExperience"
                   name="workingExperience"
                   class="input peer @error('form.party.workingExperience') input-error @enderror"
                   placeholder=" "
                   data-input-counter
                   min="1"
                   required
            />
            <label for="workingExperience" class="label">
                {{__('forms.workingExperience')}} * {{-- Add asterisk for required field --}}
            </label>
            @error('form.party.workingExperience')
            <p class="text-error">{{$message}}</p>
            @enderror
        </div>
    </div>

    <div class="form-row">
        <div class="form-group group">
            <label for="aboutMyself" class="label-secondary text-gray-500 dark:text-gray-400">
                {{__('forms.aboutMyself')}}
            </label>

            <textarea wire:model="form.party.aboutMyself"
                      id="aboutMyself"
                      class="textarea"
                      rows="4"
            >

            </textarea>
        </div>
    </div>

    {{-- Using Alpine to dynamically add and remove phone input fields --}}
    <div class="mb-4" x-data="{ phones: $wire.entangle('form.party.phones') }">

        <template x-for="(phone, index) in phones" :key="index">
            <div class="form-row-3 md:mb-0">

                <div class="form-group group">
                    <label for="phoneType-@{{ index }}" class="sr-only">{{__('forms.type_mobile')}}</label>
                    <select x-model = "phone.type" id="phoneType-@{{ index }}" class="input-select peer"
                            :class="{ 'input-error': $wire.errors.has('form.party.phones.' + index + '.type') }"
                            required>
                        <option selected>{{__('forms.type_mobile')}} *</option>
                        @foreach($this->dictionaries['PHONE_TYPE'] as $k => $phoneType )
                            <option value="{{$k}}">{{$phoneType}}</option>
                        @endforeach
                    </select>
                    <p class="text-error" x-text="$wire.errors.get('form.party.phones.' + index + '.type')" x-show="$wire.errors.has('form.party.phones.' + index + '.type')"></p>
                </div>

                <div class="form-group group"
                     x-data
                     x-init="
        const inputElement = $el.querySelector('input[type=\'tel\']');

        const maskOptions = {
            mask: '+{380} (00) 000-00-00',
            lazy: false,
            placeholderChar: '_'
        };

        const mask = IMask(inputElement, maskOptions);

        mask.value = phone.number || '';

        // FIX: Use the 'accept' event from imask.js instead of the 'input' event.
        // This guarantees that we get the value AFTER the mask has processed it.
        mask.on('accept', () => {
            const rawValue = mask.value;
            const digits = rawValue.replace(/[^0-9]/g, '');
            const cleanValue = `+${digits}`;

            if (phone.number !== cleanValue) {
                phone.number = cleanValue;
            }
        });
     "
                >

                    <input
                        type="tel"
                        name="phone-@{{ index }}"
                        id="phoneNumber-@{{ index }}"
                        class="input peer"
                        :class="{ 'input-error': $wire.errors.has('form.party.phones.' + index + '.number') }"
                        placeholder=" "
                        required
                    />
                    <label for="phoneNumber-@{{ index }}" class="label">
                        {{__('forms.phone')}}
                    </label>
                    <p class="text-error text-xs" x-show="$wire.errors.has('form.party.phones.' + index + '.number')">
                        <span x-text="$wire.errors.get('form.party.phones.' + index + '.number')"></span>
                    </p>
                </div>

                <template x-if="index == phones.length - 1 & index != 0">
                    <button x-on:click="phones.pop(), index--"
                            class="item-remove"
                    >

                        {{__('forms.remove_phone')}}
                    </button>
                </template>

                <template x-if="index == phones.length - 1">
                    <button x-on:click="phones.push({ type: '', number: '' })"
                            class="item-add lg:justify-self-start"
                            :class="{ 'lg:justify-self-start': index > 0 }"
                    >

                        {{__('forms.add_phone')}}
                    </button>
                </template>
            </div>
        </template>

    </div>
</fieldset>
