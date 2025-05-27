<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ $pageTitle }}</x-slot>
    </x-section-navigation>

    <section class="section-form">
        <form wire:submit.prevent="{{ $formAction }}" class="form" x-data="employeeRequestForm()" x-init="init">
            @yield('form-content')

            {{-- Універсальне модальне вікно підпису --}}
            @if($showSignatureOptions)
                <x-forms.signature-modal
                    modal-id="showSignModal"
                    submit-method="submitForApproval"
                    :certificate-authorities="$certificateAuthorities"
                />
            @endif

            <div class="form-button-group">
                @yield('form-buttons')
            </div>
        </form>
    </section>

    <x-forms.loading />
</div>

<script>
    function employeeRequestForm() {
        return {
            showSignModal: false,
            isDoctor: false,
            isFormValid: false,

            init() {
                this.updateDoctorStatus(this.$wire.form.party?.employee_type);
                this.$watch('$wire.form', (form) => {
                    this.updateDoctorStatus(form.party?.employee_type);
                    this.checkFormValidity(form);
                }, { deep: true });
            },

            updateDoctorStatus(employeeType) {
                const doctorTypes = {{ Js::from(config('ehealth.doctors_type')) }};
                this.isDoctor = doctorTypes.includes(employeeType);
            },

            checkFormValidity(form) {
                // Базова валідація, може бути розширена в дочірніх шаблонах
                this.isFormValid =
                    !!form.party?.employee_type &&
                    !!form.party?.first_name &&
                    !!form.party?.last_name;
            },

            prepareForSigning() {
                this.$wire.prepareForSigning().then(() => {
                    this.showSignModal = true;
                });
            }
        }
    }
</script>
