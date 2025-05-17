<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              x-data="{
                  educations: $wire.entangle('form.educations'),
                  openModal: false,
                  modalEducation: new Education(),
                  newEducation: false,
                  item: 0,
                  specDict: @js($this->dictionaries['SPECIALITY_TYPE']),
                  degreeDict: @js($this->dictionaries['EDUCATION_DEGREE']),
                  countryDict: @js($this->dictionaries['COUNTRY']),
              }"
    >
        <legend class="legend">
            <h2>{{ __('forms.education') }}</h2>
        </legend>

        <table class="table-input w-inherit">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input">{{ __('forms.country') }}</th>
                <th scope="col" class="th-input">{{ __('forms.city') }}</th>
                <th scope="col" class="th-input">{{ __('forms.institutionName') }}</th>
                <th scope="col" class="th-input">{{ __('forms.speciality') }}</th>
                <th scope="col" class="th-input">{{ __('forms.degree') }}</th>
                <th scope="col" class="th-input">{{ __('forms.issuedDate') }}</th>
                <th scope="col" class="th-input">{{ __('forms.diplomaNumber') }}</th>
                <th scope="col" class="th-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(education, index) in educations">
                <tr>
                    <td class="td-input" x-text="countryDict[education.country] || educations.country"></td>
                    <td class="td-input" x-text="education.city"></td>
                    <td class="td-input" x-text="education.institution_name"></td>
                    <td class="td-input" x-text="specDict[education.speciality] || educations.speciality"></td>
                    <td class="td-input" x-text="degreeDict[education.degree] || education.degree"></td>
                    <td class="td-input" x-text="education.issued_date"></td>
                    <td class="td-input" x-text="education.diploma_number"></td>
                    <td class="td-input">
                        relative">
                        <!-- Кнопки редагування та видалення -->
                        <x-dropdown-button
                            :editAction="'openModal = true; item = index; modalEducation = new Education(education); newEducation = false; close($refs.button)'"
                            :deleteAction="'educations.splice(index, 1); close($refs.button)'"
                        />
                    </td>
                </tr>
            </template>
            </tbody>
        </table>

        <div>
            <button @click="
                        openModal = true;
                        newEducation = true;
                        modalEducation = new Education();
                    "
                    @click.prevent
                    class="item-add my-5"
            >
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/>
                </svg>
                {{__('forms.addEducation')}}
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
                             class="modal-content h-fit"
                        >
                            <h3 class="modal-header" :id="$id('modal-title')">
                                <span x-text="newEducation ? '{{ __('forms.addEducation') }}' : '{{ __('forms.edit') . ' ' . __('forms.education') }}'"></span>
                            </h3>

                            <form>
                                <div class="form-row-modal grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="educationCountry" class="label-modal">{{__('forms.country')}}</label>
                                        <select x-model="modalEducation.country" id="educationCountry" class="input-modal" required>
                                            @foreach($this->dictionaries['COUNTRY'] as $typeValue => $typeDescription)
                                                <option value="{{$typeValue}}">{{$typeDescription}}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-error text-xs"
                                           x-show="!Object.keys(dictionary).includes(modalEducation.country)">{{__('forms.field_empty')}}</p>
                                        <p class="text-error text-xs" x-show="!modalEducation.country.trim().length > 0">{{__('forms.field_empty')}}</p>
                                    </div>
                                    <div>
                                        <label for="educationCity" class="label-modal">{{__('forms.city')}}</label>
                                        <input x-model="modalEducation.city" type="text" id="educationCity" class="input-modal" required>
                                        <p class="text-error text-xs" x-show="!modalEducation.city.trim().length > 0">{{__('forms.field_empty')}}</p>
                                    </div>
                                    <div>
                                        <label for="educationInstitution" class="label-modal">{{__('forms.institutionName')}}</label>
                                        <input x-model="modalEducation.institution_name" type="text" id="educationInstitution" class="input-modal" required>
                                        <p class="text-error text-xs" x-show="!modalEducation.institution_name.trim().length > 0">{{__('forms.field_empty')}}</p>
                                    </div>
                                    <div>
                                        <label for="educationSpeciality" class="label-modal">{{__('forms.speciality')}}</label>
                                        <select x-model="modalEducation.speciality" id="educationSpeciality" class="input-modal" required>
                                            @foreach($this->dictionaries['SPECIALITY_TYPE'] as $typeValue => $typeDescription)
                                                <option value="{{$typeValue}}">{{$typeDescription}}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-error text-xs"
                                           x-show="!Object.keys(dictionary).includes(modalEducation.speciality)">{{__('forms.field_empty')}}</p>
                                    </div>
                                    <div>
                                        <label for="educationDegree" class="label-modal">{{__('forms.degree')}}</label>
                                        <select x-model="modalEducation.degree" id="educationDegree" class="input-modal" required>
                                            @foreach($this->dictionaries['EDUCATION_DEGREE'] as $typeValue => $typeDescription)
                                                <option value="{{$typeValue}}">{{$typeDescription}}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-error text-xs"
                                           x-show="!Object.keys(dictionary).includes(modalEducation.degree)">{{__('forms.field_empty')}}</p>
                                    </div>
                                    <div>
                                        <label for="educationIssuedDate" class="label-modal">{{__('forms.issuedDate')}}</label>
                                        <input id="educationIssuedDate" x-model="modalEducation.issued_date"  class="input-modal datepicker-input"
                                               autocomplete="off" required>
                                    </div>
                                    <div>
                                        <label for="educationDiplomaNumber" class="label-modal">{{__('forms.diplomaNumber')}}</label>
                                        <input x-model="modalEducation.diploma_number" type="text" id="educationDiplomaNumber" class="input-modal">
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-between space-x-2">
                                    <button type="button"
                                            @click="openModal = false"
                                            class="button-minor"
                                    >
                                        {{__('forms.cancel')}}
                                    </button>

                                    <button @click.prevent
                                            @click="newEducation ? educations.push(modalEducation) : educations[item] = modalEducation; openModal = false"
                                            class="button-primary"
                                            :disabled="!(modalEducation.country.trim().length > 0 &&
                                                      modalEducation.city.trim().length > 0 &&
                                                      modalEducation.institution_name.trim().length > 0 &&
                                                      modalEducation.speciality.trim().length > 0)"
                                    >
                                        {{__('forms.save')}}
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
    class Education {
        country = '';
        city = '';
        institution_name = '';
        speciality = '';
        degree = '';
        issued_date = '';
        diploma_number = '';

        constructor(obj = null) {
            if (obj) {
                Object.assign(this, obj);
            }
        }
    }
</script>
