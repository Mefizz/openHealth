<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              x-data="{
                  specialities: $wire.entangle('form.specialities'),
                  openModal: false,
                  modalSpeciality: new Speciality(),
                  newSpeciality: false,
                  item: 0,
                  specDict: $wire.dictionaries['SPECIALITY_TYPE'],
                  levelDict: $wire.dictionaries['SPECIALITY_LEVEL']
              }"
    >
        <h5 class="mb-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            {{ __('forms.specialities') }}
        </h5>

        <table class="table-input w-full">
            <thead class="thead-input">
            <tr>
                <th class="th-input">{{ __('Спеціальність') }}</th>
                <th class="th-input">{{ __('Орган що видав') }}</th>
                <th class="th-input">{{ __('Рівень спеціальності') }}</th>
                <th class="th-input">{{ __('Номер свідоцтва') }}</th>
                <th class="th-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>


            <template x-for="(speciality, index) in specialities" :key="index">
                <tr>
                    <td class="td-input" x-text="specDict[speciality.speciality] || speciality.speciality"></td>
                    <td class="td-input" x-text="speciality.attestation_name"></td>
                    <td class="td-input" x-text="levelDict[speciality.level] || speciality.level"></td>
                    <td class="td-input" x-text="speciality.certificate_number"></td>
                    <td class="td-input">
                        <x-dropdown-button
                            :editAction="'openModal = true; item = index; modalEducation = new Education(education); newEducation = false; close($refs.button)'"
                            :deleteAction="'educations.splice(index, 1); close($refs.button)'"
                        />
                    </td>
                </tr>
            </template>

            </tbody>
        </table>

        <!-- Кнопка додавання -->
        <button @click="
                        openModal = true;
                        newSpeciality = true;
                        modalSpeciality = new Speciality()
                    "
                @click.prevent
                class="item-add my-5"
        >
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                 viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 12h14m-7 7V5"/>
            </svg>

            {{__('forms.addSpeciality')}}
        </button>

        <!-- Modal -->
        <template x-teleport="body">
            <div x-show="openModal"
                 style="display: none"
                 @keydown.escape.prevent.stop="openModal = false"
                 role="dialog"
                 aria-modal="true"
                 x-id="['modal-title']"
                 :aria-labelledby="$id('modal-title')"
                 class="modal"
            >
                <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-black/40 z-40"></div>

                <div x-show="openModal"
                     x-transition
                     @click="openModal = false"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div @click.stop
                         x-trap.noscroll.inert="openModal"
                         class="w-full max-w-lg bg-gray-800 text-white rounded-lg shadow-lg p-6"
                    >
                        <h2 class="text-xl font-semibold mb-6" :id="$id('modal-title')">
                            <span
                                x-text="newSpeciality ? '{{ __('forms.addSpeciality') }}' : '{{ __('forms.edit') . ' ' . __('forms.speciality') }}'"></span>
                        </h2>

                        <form>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="specialityType" class="block mb-1 text-sm font-medium">
                                        {{ __('Спеціальність') }}
                                    </label>

                                    <select id="specialityType"
                                            x-model="modalSpeciality.speciality"
                                            class="input-modal"
                                            required>
                                        <option value="">{{ __('forms.speciality') }}</option>
                                        @foreach($this->dictionaries['SPECIALITY_TYPE'] as $typeValue => $typeDescription)
                                            <option value="{{ $typeValue }}">{{ $typeDescription }}</option>
                                        @endforeach
                                    </select>

                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="modalSpeciality.speciality && !Object.keys(dictionary).includes(modalSpeciality.speciality)">
                                        {{ __('forms.invalid_selection') }}
                                    </p>

                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalSpeciality.speciality">
                                        {{ __('forms.field_empty') }}
                                    </p>
                                </div>

                                <div class="flex flex-col justify-end">
                                    <label class="inline-flex items-center mt-6">
                                        <input type="checkbox" x-model="modalSpeciality.speciality_officio"
                                               class="h-4 w-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-500 focus:ring-2">
                                        <span class="ml-2 text-sm">{{ __('forms.specialityOfficio') }}</span>
                                    </label>
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="modalSpeciality.speciality_officio === null || modalSpeciality.speciality_officio === undefined">
                                        {{ __('forms.field_empty') }}
                                    </p>
                                </div>

                                <div>
                                    <label for="specAttestation"
                                           class="block mb-1 text-sm font-medium">{{ __('Орган що видав') }}</label>
                                    <input type="text" id="specAttestation" x-model="modalSpeciality.attestation_name"
                                           class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                           required>
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalSpeciality.attestation_name">{{ __('forms.field_empty') }}</p>
                                </div>

                                <div>
                                    <label for="specLevel" class="block mb-1 text-sm font-medium">
                                        {{ __('forms.speciality_level') }}
                                    </label>

                                    <select id="specLevel"
                                            x-model="modalSpeciality.level"
                                            class="input-modal"
                                            required>
                                        <option value="">{{ __('forms.speciality_level') }}</option>
                                        @foreach($this->dictionaries['SPECIALITY_LEVEL'] as $typeValue => $typeDescription)
                                            <option value="{{ $typeValue }}">{{ $typeDescription }}</option>
                                        @endforeach
                                    </select>

                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="modalSpeciality.level && !Object.keys(dictionary).includes(modalSpeciality.leve
                                       )">
                                        {{ __('forms.invalid_selection') }}
                                    </p>

                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalSpeciality.level">
                                        {{ __('forms.field_empty') }}
                                    </p>
                                </div>

                                <div>
                                    <label for="specCertificate"
                                           class="block mb-1 text-sm font-medium">{{ __('forms.certificate_number') }}</label>
                                    <input type="text" id="specCertificate" x-model="modalSpeciality.certificate_number"
                                           class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-4">
                                <button type="button"
                                        @click="openModal = false"
                                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                    {{ __('forms.cancel') }}
                                </button>
                                <button type="submit"
                                        @click.prevent="newSpeciality ? specialities.push(modalSpeciality) : specialities[item] = modalSpeciality; openModal = false"
                                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                        :disabled="!(modalSpeciality.speciality && modalSpeciality.attestation_name && modalSpeciality.level)">
                                    {{ __('forms.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

    </fieldset>
</div>

<script>
    class Speciality {
        speciality = '';
        speciality_officio = '';
        attestation_name = '';
        level = '';
        certificate_number = '';

        constructor(obj = null) {
            if (obj) Object.assign(this, obj);
        }
    }
</script>
