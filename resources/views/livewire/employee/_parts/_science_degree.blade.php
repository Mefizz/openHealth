<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              x-data="{
                  scienceDegree: $wire.entangle('form.science_degree'),
                  openModal: false,
                  modalScienceDegree: new ScienceDegree(),
                  dictionary: {
                      'BACHELOR': '{{ __('forms.bachelor') }}',
                      'MASTER': '{{ __('forms.master') }}',
                      'PHD': '{{ __('forms.phd') }}',
                      'ASSOCIATE': '{{ __('forms.associate') }}',
                      'SPECIALIST': '{{ __('forms.specialist') }}'
                  }
              }"
    >
        <legend class="legend">
            <h2>{{ __('forms.scienceDegree') }}</h2>
        </legend>


        <template x-if="true">
            <table class="table-input w-full">
                <thead class="thead-input">
                <tr>
                    <th class="th-input">{{ __('forms.degree') }}</th>
                    <th class="th-input">{{ __('forms.issuedDate') }}</th>
                    <th class="th-input">{{ __('forms.institutionName') }}</th>
                    <th class="th-input">{{ __('forms.speciality') }}</th>
                    <th class="th-input">{{ __('forms.diplomaNumber') }}</th>
                    <th class="th-input">{{ __('forms.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr x-show="scienceDegree">
                    <td class="td-input" x-text="dictionary[scienceDegree.degree] || scienceDegree.degree"></td>
                    <td class="td-input" x-text="scienceDegree.issued_date"></td>
                    <td class="td-input" x-text="scienceDegree.institution_name"></td>
                    <td class="td-input" x-text="scienceDegree.speciality"></td>
                    <td class="td-input" x-text="scienceDegree.diploma_number"></td>
                    <td class="td-input">
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
                            modalScienceDegree = new ScienceDegree();
                            newScienceDegree = false;
                            openDropdown = false;
                        "
                                    class="block w-full text-left px-3 py-1 text-sm hover:bg-gray-100"
                                >
                                    {{ __('forms.edit') }}
                                </button>
                                <button
                                    @click.prevent="scienceDegree.splice(index, 1); openDropdown = false;"
                                    class="block w-full text-left px-3 py-1 text-sm text-red-600 hover:bg-red-100"
                                >
                                    {{ __('forms.delete') }}
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </template>

        <!-- Кнопка додавання -->
        <button @click="
                        openModal = true;
                        newScienceDegree = true;
                        modalScienceDegree = new ScienceDegree(scienceDegree);
                    "
                @click.prevent
                class="item-add my-5"
        >
            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                 viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 12h14m-7 7V5"/>
            </svg>

            {{__('forms.addScienceDegree')}}
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
                                x-text="!scienceDegree ? '{{ __('forms.addScienceDegree') }}' : '{{ __('forms.edit') . ' ' . __('forms.scienceDegree') }}'"></span>
                        </h2>

                        <form>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="scienceDegreeType"
                                           class="block mb-1 text-sm font-medium">{{ __('forms.degree') }}</label>
                                    <select x-model="modalScienceDegree.degree" id="scienceDegreeType" class="input-modal" required>
                                        @foreach($this->dictionaries['SCIENCE_DEGREE'] as $typeValue => $typeDescription)
                                            <option value="{{$typeValue}}">{{$typeDescription}}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalScienceDegree.degree">{{ __('forms.field_empty') }}</p>
                                </div>

                                <div>
                                    <label for="scienceIssued"
                                           class="block mb-1 text-sm font-medium">{{ __('forms.issuedDate') }}</label>
                                    <input type="date" id="scienceIssued" x-model="modalScienceDegree.issued_date"
                                           class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="дд.мм.рррр" required>
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalScienceDegree.issued_date">{{ __('forms.field_empty') }}</p>
                                </div>

                                <div>
                                    <label for="scienceInstitution"
                                           class="block mb-1 text-sm font-medium">{{ __('forms.institutionName') }}</label>
                                    <input type="text" id="scienceInstitution"
                                           x-model="modalScienceDegree.institution_name"
                                           class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                           required>
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalScienceDegree.institution_name">{{ __('forms.field_empty') }}</p>
                                </div>

                                <div>
                                    <label for="scienceSpeciality" class="block mb-1 text-sm font-medium">
                                        {{ __('forms.speciality') }}
                                    </label>

                                    <select id="scienceSpeciality"
                                            x-model="modalScienceDegree.speciality"
                                            class="input-modal"
                                            required>
                                        <option value="">{{ __('forms.speciality') }}</option>
                                        @foreach($this->dictionaries['SPECIALITY_TYPE'] as $typeValue => $typeDescription)
                                            <option value="{{ $typeValue }}">{{ $typeDescription }}</option>
                                        @endforeach
                                    </select>

                                    <!-- Перевірка: чи вибрано значення -->
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalScienceDegree.speciality">
                                        {{ __('forms.field_empty') }}
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="scienceDiploma"
                                           class="block mb-1 text-sm font-medium">{{ __('forms.diplomaNumber') }}</label>
                                    <input type="text" id="scienceDiploma" x-model="modalScienceDegree.diploma_number"
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
                                        @click.prevent="scienceDegree = modalScienceDegree; openModal = false"
                                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                        :disabled="!(modalScienceDegree.degree && modalScienceDegree.institution_name && modalScienceDegree.issued_date && modalScienceDegree.speciality)">
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
    class ScienceDegree {
        degree = '';
        issued_date = '';
        institution_name = '';
        speciality = '';
        diploma_number = '';

        constructor(obj = null) {
            if (obj) {
                Object.assign(this, obj);
            }
        }
    }
</script>
