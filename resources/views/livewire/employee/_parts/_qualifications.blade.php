<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              x-data="{
                  qualifications: $wire.entangle('form.qualifications'),
                  openModal: false,
                  modalQualification: new Qualification(),
                  newQualification: false,
                  item: 0,
                  qualTypeDict: $wire.dictionaries['QUALIFICATION_TYPE']
              }"
    >
        <legend class="legend">
            <h2>{{ __('forms.qualifications') }}</h2>
        </legend>

        <table class="table-input w-inherit">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input">{{ __('forms.document_type') }}</th>
                <th scope="col" class="th-input">{{ __('forms.institutionName') }}</th>
                <th scope="col" class="th-input">{{ __('forms.speciality') }}</th>
                <th scope="col" class="th-input">{{ __('forms.certificateNumber') }}</th>
                <th scope="col" class="th-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>

            <template x-for="(qualification, index) in qualifications" :key="index">
                <tr>
                    <td class="td-input" x-text="qualTypeDict[qualification.type] || qualification.type"></td>
                    <td class="td-input" x-text="qualification.institution_name"></td>
                    <td class="td-input" x-text="qualification.speciality"></td>
                    <td class="td-input" x-text="qualification.certificate_number"></td>
                    <td class="td-input relative">
                        <div x-data="{ openDropdown: false }" class="relative">
                            <button
                                @click="openDropdown = !openDropdown"
                                @click.outside="openDropdown = false"
                                x-ref="button"
                                type="button"
                                class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700"
                            >
                                <svg class="w-6 h-6 text-gray-800 dark:text-gray-200" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                     viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                </svg>
                            </button>

                            <div
                                x-show="openDropdown"
                                x-transition:enter="transition transform duration-300 ease-out"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition transform duration-200 ease-in"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-2"
                                @click.outside="openDropdown = false"
                                x-cloak
                                class="absolute top-0 left-1/2 transform -translate-x-1/2 z-10 bg-white shadow-lg dropdown-panel p-2 rounded w-32"
                                style="top: -100%;"
                            >
                                <button
                                    @click.prevent="
                            openModal = true;
                            item = index;
                            modalQualification = new Qualification(qualification);
                            newQualification = false;
                            openDropdown = false;
                        "
                                    class="block w-full text-left px-3 py-1 text-sm hover:bg-gray-100"
                                >
                                    {{ __('forms.edit') }}
                                </button>
                                <button
                                    @click.prevent="qualifications.splice(index, 1); openDropdown = false;"
                                    class="block w-full text-left px-3 py-1 text-sm text-red-600 hover:bg-red-100"
                                >
                                    {{ __('forms.delete') }}
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            </template>

            </tbody>
        </table>

        <div>
            <button @click="openModal = true; newQualification = true; modalQualification = new Qualification();"
                    @click.prevent
                    class="item-add my-5 text-white"
            >
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 12h14m-7 7V5"/>
                </svg>
                {{__('forms.addQualification')}}
            </button>

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
                    <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-black/25"></div>

                    <div x-show="openModal"
                         x-transition
                         @click="openModal = false"
                         class="relative flex min-h-screen items-center justify-center p-4"
                    >
                        <div @click.stop
                             x-trap.noscroll.inert="openModal"
                             class="modal-content h-fit bg-gray-800 text-white"
                        >
                            <h3 class="modal-header" :id="$id('modal-title')">
                                <span
                                    x-text="newQualification ? '{{ __('Додати підвищення кваліфікації') }}' : '{{ __('Редагувати підвищення кваліфікації') }}'"></span>
                            </h3>

                            <form>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="qualType"
                                               class="block mb-1 text-sm font-medium">{{ __('forms.document_type') }}</label>
                                        <input type="text" id="qualType" x-model="modalQualification.type"
                                               class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                               required>
                                    </div>

                                    <div>
                                        <label for="qualInstitution"
                                               class="block mb-1 text-sm font-medium">{{ __('forms.institutionName') }}</label>
                                        <input type="text" id="qualInstitution"
                                               x-model="modalQualification.institution_name"
                                               class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                               required>
                                    </div>

{{--                                    <div>--}}
{{--                                        <label for="qualSpeciality"--}}
{{--                                               class="block mb-1 text-sm font-medium">{{ __('forms.speciality') }}</label>--}}
{{--                                        <input type="text" id="qualSpeciality" x-model="modalQualification.speciality"--}}
{{--                                               class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"--}}
{{--                                               required>--}}
{{--                                    </div>--}}

                                    <div>
                                        <label for="specialityType" class="block mb-1 text-sm font-medium">
                                            {{ __('forms.speciality') }}
                                        </label>

                                        <select id="qualificationSpeciality"
                                                x-model="modalQualification.speciality"
                                                class="input-modal"
                                                required>
                                            <option value="">{{ __('forms.speciality') }}</option>
                                            @foreach($this->dictionaries['SPECIALITY_TYPE'] as $typeValue => $typeDescription)
                                                <option value="{{ $typeValue }}">{{ $typeDescription }}</option>
                                            @endforeach
                                        </select>

                                        <!-- Перевірка: чи значення з dictionary -->
                                        <p class="text-red-500 text-xs mt-1"
                                           x-show="modalQualification.speciality && !Object.keys(dictionary).includes(modalQualification.speciality)">
                                            {{ __('forms.invalid_selection') }}
                                        </p>

                                        <!-- Перевірка: чи вибрано значення -->
                                        <p class="text-red-500 text-xs mt-1"
                                           x-show="!modalQualification.speciality">
                                            {{ __('forms.field_empty') }}
                                        </p>
                                    </div>

                                    <div>
                                        <label for="qualificationCertificate"
                                               class="block mb-1 text-sm font-medium">{{ __('forms.certificateNumber') }}</label>
                                        <input type="text" id="qualificationCertificate"
                                               x-model="modalQualification.certificate_number"
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
                                            @click.prevent="newQualification ? qualifications.push(modalQualification) : qualifications[item] = modalQualification; openModal = false"
                                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                            :disabled="!(modalQualification.type && modalQualification.institution_name && modalQualification.speciality)">
                                        {{ __('forms.save') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </fieldset>
</div>

<script>
    class Qualification {
        type = '';
        institution_name = '';
        speciality = '';
        certificate_number = '';

        constructor(obj = null) {
            if (obj) Object.assign(this, obj);
        }
    }
</script>
