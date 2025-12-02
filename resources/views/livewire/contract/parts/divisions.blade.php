<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{ __('forms.divisions') }}</h2>
    </legend>

    <p class="default-p mb-6">{{ __('contracts.divisions_info') }}</p>

    <div x-data="{ divisions: $wire.entangle('form.contractorDivisions') }"
         x-init="if (!Array.isArray(divisions) || divisions.length === 0) { divisions = [''] }"
    >
        <div id="division-fields-container">
            <template x-for="(divisionId, index) in divisions" :key="index">
                <div class="form-row-3 division-input-group" :class="{'mt-4': index > 0}">
                    <div class="form-group group">
                        <select x-model="divisions[index]"
                                type="text"
                                :name="'divisionName_' + index"
                                :id="'divisionName_' + index"
                                class="input-select"
                                :class="{ 'input-error': $wire.errors.has(`form.contractorDivisions.${index}`) }"
                        >
                            <option value="" selected>{{ __('forms.select') }}</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division['id'] }}"> {{ $division['name'] }}</option>
                            @endforeach
                        </select>
                        <label :for="'divisionName_' + index" class="label">{{ __('forms.division_name') }}</label>

                        @error('form.contractorDivisions.*')
                        <template x-if="$wire.errors.has(`form.contractorDivisions.${index}`)">
                            <p class="text-error" x-text="$wire.errors.get(`form.contractorDivisions.${index}`)"></p>
                        </template>
                        @enderror
                    </div>

                    <div class="flex items-center space-x-4 justify-start mt-2 md:mt-0">
                        <template x-if="divisions.length > 1">
                            <button type="button"
                                    @click.prevent="divisions.splice(index, 1)"
                                    class="item-remove text-error ml-2"
                            >
                                @icon('delete', 'w-5 h-5 text-red-600')
                            </button>
                        </template>

                        <template x-if="index === divisions.length - 1">
                            <button type="button"
                                    @click.prevent="divisions.push('')"
                                    class="item-add"
                                    id="add-division-button-hidden"
                            >
                                {{ __('forms.add_new_division') }}
                            </button>
                        </template>
                    </div>

                </div>
            </template>
        </div>
    </div>
</fieldset>

