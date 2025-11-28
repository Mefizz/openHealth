<fieldset class="fieldset">
    <legend class="legend">
        <h2> {{ __('forms.divisions') }}</h2>
    </legend>

    <p class="default-p mb-6"> {{ __('contracts.divisions_info') }}</p>

    <div id="division-fields-container">
        <div class="form-row-3 division-input-group">
            <div class="form-group group">
                <select wire:model="form.contractorDivisions.0"
                        type="text"
                        name="divisionName"
                        id="divisionName"
                        class="input-select"
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division['id'] }}"> {{ $division['name'] }}</option>
                    @endforeach
                </select>
                <label for="divisionName" class="label">{{ __('forms.division_name') }}</label>

                @error('form.contractorDivisions')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="form-group mt-4">
        <button type="button" class="item-add" id="add-division-button" onclick="addNewDivision()">
            {{ __('forms.add_new_division') }}
        </button>
    </div>
</fieldset>

<script>
    let divisionIndex = 1;

    const availableDivisions = [
            @foreach($divisions as $division)
        { id: '{{ $division['id'] }}', name: '{{ $division['name'] }}' },
        @endforeach
    ];

    const selectPlaceholder = '{{ __('forms.select') }}';
    const divisionLabel = '{{ __('forms.division_name') }}';

    function addNewDivision() {
        const container = document.getElementById('division-fields-container');
        if (!container) return;

        const newGroupContainer = document.createElement('div');
        newGroupContainer.classList.add('form-row-3', 'division-input-group', 'mt-4');

        const newFieldName = `divisionName_${divisionIndex}`;

        let optionsHtml = `<option value="" selected>${selectPlaceholder}</option>`;
        availableDivisions.forEach(division => {
            optionsHtml += `<option value="${division.id}">${division.name}</option>`;
        });

        newGroupContainer.innerHTML = `
            <div class="form-group group">
                <select wire:model="form.contractorDivisions.${divisionIndex}"
                        type="text"
                        name="${newFieldName}"
                        id="${newFieldName}"
                        class="input-select"
                >
                    ${optionsHtml}
                </select>
                <label for="${newFieldName}" class="label">${divisionLabel}</label>
        </div>
        <button type="button" class="text-error ml-2" onclick="removeDivisionField(this)">
              @icon('delete', 'w-5 h-5 text-red-600')
        </button>
`;
        container.appendChild(newGroupContainer);
        divisionIndex++;
    }

    function removeDivisionField(buttonElement) {
        const inputGroup = buttonElement.closest('.division-input-group');
        if (inputGroup) {
            inputGroup.remove();
        }
    }
</script>
