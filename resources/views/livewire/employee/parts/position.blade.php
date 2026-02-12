<fieldset class="fieldset"
          x-data="{
              employeeType: $wire.entangle('form.employeeType'),
              employeeTypePosition: @js($this->employeeTypePosition)
          }"
          x-init="$watch('employeeType', (value) => {
              /* Reset position only if fields are not locked (Creation/Add mode) */
              if (!$wire.isPositionDataLocked) {
                  $wire.set('form.position', '', false);
                  if (document.getElementById('position')) {
                      document.getElementById('position').value = '';
                  }
              }
          })"
>
    <legend class="legend">
        <h2>{{ __('forms.position') }}</h2>
    </legend>

    <div class="form-row-3">
        {{-- 1. Employee Type: Locked based on component state --}}
        <div class="form-group">
            <select name="employeeType"
                    id="employeeType"
                    class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                    required
                    wire:model="form.employeeType"
                    x-model="employeeType"
                    :disabled="$wire.isPositionDataLocked">
                <option value="" disabled selected hidden>{{ __('forms.role_choose') }}</option>

                @foreach($this->dictionaries['EMPLOYEE_TYPE'] as $employeeTypes => $employeeTypeOption)
                    @if($employeeTypes === 'OWNER')
                        @continue
                    @endif

                    <option value="{{ $employeeTypes }}">{{ $employeeTypeOption }}</option>
                @endforeach

            </select>
            <label for="employeeType" class="label">{{ __('forms.role') }}</label>
            @error('form.employeeType') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- 2. Position (Text Input with Datalist) --}}
        <div class="form-group">
            <input list="positions-list"
                   type="text"
                   id="position"
                   name="position"
                   class="peer input"
                   placeholder=" "
                   wire:model="form.position"
                   required
                   :disabled="$wire.isPositionDataLocked"
            />
            <datalist id="positions-list">
                <template x-for="pos in employeeTypePosition[employeeType] || []">
                    <option :value="pos"></option>
                </template>
            </datalist>
            <label for="position" class="label">{{ __('forms.position') }}</label>
            @error('form.position') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- 3. Start Date --}}
        <div class="form-group">
            <input type="date"
                   id="start_date"
                   name="start_date"
                   class="peer input"
                   wire:model="form.startDate"
                   required
                   :disabled="$wire.isPositionDataLocked"
            />
            <label for="start_date" class="label">{{ __('forms.start_date') }}</label>
            @error('form.startDate') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-row-2">
        {{-- 4. Division Select --}}
        <div class="form-group">
            <select name="division" id="division"
                    class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                    wire:model="form.divisionId">
                <option value="">{{ __('forms.select_division') }}</option>
                @foreach($this->divisions as $division)
                    <option value="{{ $division['id'] }}">{{ $division['name'] }}</option>
                @endforeach
            </select>
            <label for="division" class="label">{{ __('forms.division') }}</label>
            @error('form.divisionId') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- 5. Email: Locked based on component state --}}
        @if (!empty($partyUsers))
            <div class="form-group" x-transition wire:key="party-user-email-select">
                <select name="formEmail" id="formEmail"
                        class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                        required wire:model="formEmail"
                        :disabled="$wire.isPositionDataLocked">
                    <option value="" disabled>{{ __('forms.select_user_email') }}</option>
                    @foreach($partyUsers as $user)
                        <option value="{{ $user->email }}">{{ $user->email }}</option>
                    @endforeach
                </select>
                <label for="formEmail" class="label">{{ __('forms.email') }}</label>
                @error('formEmail') <p class="text-error">{{ $message }}</p> @enderror
            </div>
        @endif
    </div>
</fieldset>
