<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              {{-- Binding documents to Alpine, it will be re-used in the modal.
                Note that it's necessary for modal to work properly --}}
              x-data="{
                  scienceDegree: $wire.entangle('form.scienceDegree'),
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
                    <td class="td-input relative">
                        <!-- Кнопки редагування та видалення -->
                        <x-dropdown-button
                            :editAction="'openModal = true; item = index; modalScienceDegree = new ScienceDegree(sciencedegree); newScienceDegree = false; close($refs.button)'"
                            :deleteAction="'scienceDegree = null; close($refs.button)'"
                        />
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

                {{-- Overlay --}}
                <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-black/40 z-40"></div>

                {{-- Panel --}}
                <div x-show="openModal"
                     x-transition
                     @click="openModal = false"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                >
                    <div @click.stop
                         x-trap.noscroll.inert="openModal"
                         class="modal-content h-fit rounded-lg shadow-lg"
                    >
                        {{-- Title --}}
                        <h3 class="modal-header" :id="$id('modal-title')">
                            <span
                                x-text="!scienceDegree ? '{{ __('forms.addScienceDegree') }}' : '{{ __('forms.edit') . ' ' . __('forms.scienceDegree') }}'"></span>
                        </h3>

                        {{-- Content --}}
                        <form>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="scienceDegreeType"
                                           class="label-modal">{{ __('forms.degree') }}</label>
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
                                           class="label-modal">{{ __('forms.issuedDate') }}</label>
                                    <input type="date" id="scienceIssued" x-model="modalScienceDegree.issued_date"
                                           class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="дд.мм.рррр" required>
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalScienceDegree.issued_date">{{ __('forms.field_empty') }}</p>
                                </div>

                                <div>
                                    <label for="scienceInstitution"
                                           class="label-modal">{{ __('forms.institutionName') }}</label>
                                    <input type="text" id="scienceInstitution"
                                           x-model="modalScienceDegree.institution_name"
                                           class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                           required>
                                    <p class="text-red-500 text-xs mt-1"
                                       x-show="!modalScienceDegree.institution_name">{{ __('forms.field_empty') }}</p>
                                </div>

                                <div>
                                    <label for="scienceSpeciality" class="label-modal">
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
                                           class="label-modal">{{ __('forms.diplomaNumber') }}</label>
                                    <input type="text" id="scienceDiploma" x-model="modalScienceDegree.diploma_number"
                                           class="input-modal bg-gray-700 text-white border border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-4">
                                <button type="button"
                                        @click="openModal = false"
                                        class="button-minor">
                                    {{ __('forms.cancel') }}
                                </button>
                                <button type="submit"
                                        @click.prevent="scienceDegree = modalScienceDegree; openModal = false"
                                        class="button-primary"
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
