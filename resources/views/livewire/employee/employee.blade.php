<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ $pageTitle }}</x-slot>
    </x-section-navigation>

    <section class="section-form">
        <form wire:submit.prevent="save" class="form" x-data="employeeForm()" x-init="init">
            @include('livewire.employeeRequest._parts._employee')
            @include('livewire.employeeRequest._parts._documents')

            <template x-if="isDoctor">
                <div>
                    @include('livewire.employeeRequest._parts._education')
                    @include('livewire.employeeRequest._parts._specialities')
                    @include('livewire.employeeRequest._parts._science_degree')
                    @include('livewire.employeeRequest._parts._qualifications')
                </div>
            </template>

            {{-- Універсальне модальне вікно підпису --}}
            <x-forms.signature-modal
                modal-id="showSignModal"
                submit-method="submitForApproval"
                :certificate-authorities="$certificateAuthorities"
                :knedp="$knedp"
                :keyContainer="$keyContainer"
                :password="$password"
            />

            <div class="form-button-group">
                <button type="button" class="button-minor" wire:click="cancel">
                    {{ __('forms.cancel') }}
                </button>

                <button type="submit" class="button-primary">
                    {{ __('forms.save') }}
                </button>


                <div>
                    <x-forms.form-field label="ПІБ">
                        <input type="text" wire:model.defer="form.fullName" class="input" />
                    </x-forms.form-field>

                    <x-forms.form-field label="ІПН">
                        <input type="text" wire:model.defer="form.taxId" class="input" />
                    </x-forms.form-field>

                    <x-forms.form-field label="Посада">
                        <select wire:model.defer="form.position" class="input">
                            <option value="">Оберіть</option>
                            <option value="DOCTOR">Лікар</option>
                            <option value="NURSE">Медсестра</option>
                        </select>
                    </x-forms.form-field>

                    <x-buttons.primary wire:click="submit">Підписати та надіслати</x-buttons.primary>
                </div>

                <button
                    type="button"
                    class="button-primary"
                    x-bind:disabled="!isFormValid"
                    @click="prepareForSigning"
                >
                    {{ __('forms.send_for_approval') }}
                </button>
            </div>
        </form>
    </section>

    <x-forms.loading />
</div>

<script>
    function employeeForm() {
        return {
            showSignModal: false,
            isDoctor: false,
            isFormValid: false,

            init() {
                this.updateDoctorStatus(this.$wire.form.party?.employeeType);

                this.$watch('$wire.form', (form) => {
                    this.updateDoctorStatus(form.party?.employeeType);
                    this.checkFormValidity(form);
                }, { deep: true });
            },

            updateDoctorStatus(employeeType) {
                const doctorTypes = {{ Js::from(config('ehealth.doctors_type')) }};
                this.isDoctor = doctorTypes.includes(employeeType);
            },

            checkFormValidity(form) {
                this.isFormValid =
                    !!form.party?.employeeType &&
                    !!form.party?.firstName &&
                    !!form.party?.lastName;
            },

            prepareForSigning() {
                this.$wire.prepareForSigning().then(() => {
                    this.showSignModal = true;
                });
            }
        }
    }
</script>
