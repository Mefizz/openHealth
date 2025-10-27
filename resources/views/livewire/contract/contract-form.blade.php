<div>
    <x-section-navigation x-data="{ showFilter: false }" class=''>
        <x-slot name='title'>
            {{ $form->previous_request_id === '' ? __('forms.contract.new_contract') :  __('forms.contract.editContract', ['contract' => $form->previous_request_id]) }}
        </x-slot>
    </x-section-navigation>

    <div class='flex bg-white pb-10 p-6 flex-col'>
        {{-- LegalEntity Info --}}
            <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
                <legend class="legend">
                    <h2> {{ __('forms.legal_entity_info') }}</h2>
                </legend>
                <div class="form">
                    <div class="form-row-3">
                            <div class="form-group">
                                <input value="{{ $legalEntity['edr']['public_name'] ?? '' }}"  type="text" name="legal_entity_name" id="legal_entity_name" class="peer input" placeholder=" " required />
                                <label for="legal_entity_name" class="label">{{ __('forms.legal_entity_name') }}</label>
                                @error('form.party.firstName') <p class="text-error">{{$message}}</p> @enderror
                            </div>
                            <div class="form-group">
                                <input value="{{ $legalEntity['edr']['name'] ?? '' }}"  type="text" name="legal_entity_owner" id="legal_entity_owner" class="peer input" placeholder=" " required />
                                <label for="legal_entity_name" class="label">{{ __('forms.legal_entity_owner')}}</label>
                                @error('form.party.legal_entity_name') <p class="text-error">{{$message}}</p> @enderror
                            </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input value="{{ $legalEntity['edr']['name'] ?? '' }}" wire:model="form.contractor_base"  type="text" name="contractor_base" id="contractor_base" class="peer input" placeholder=" " required />
                            <label for="contractor_base" class="label">{{ __('forms.contract.contractorBase') }}</label>
                            @error('form.party.contractor_base') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <input {{--value="{{ $legalEntity['edr']['name'] ?? '' }}"--}} wire:model="form.number_of_people" type="number" name="numberOfPeople" id="numberOfPeople" class="peer input" placeholder=" " required />
                            <label for="numberOfPeople" class="label">{{ __('forms.contract.numberOfPeople') }}</label>
                            @error('form.party.numberOfPeople') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                    </div>

            </div>
            </fieldset>

        {{-- LegalEntity Contract Terms --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2>{{ __('forms.contract.contracts') }}</h2>
            </legend>
            <div class="form-row">
            <div class="form-group">
                <select wire:model="form.id_form" name="id_form" id="id_form" class="peer input appearance-none bg-white dark:bg-gray-800 dark:text-gray-400" required>
                    <option value="" disabled selected hidden>{{ __('forms.select') }} {{ __('forms.contract.contractType') }}</option>
                    @foreach($this->dictionaries['CONTRACT_TYPE'] as $key => $contract_type)
                        <option value="{{ $key }}">{{ $contract_type }}</option>
                    @endforeach
                </select>
                <label for="id_form" class="label">{{ __('forms.contract.contractType') }}</label>
                @error('form.id_form')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
            </div>
                        <div class="form-row-2 items-start">
                        <div class="form-group datepicker-wrapper relative w-full">
                            <input wire:model="form.start_date" type="text" name="start_date" id="start_date" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                            <label for="start_date" class="wrapped-label">{{ __('forms.contract.startDateContract') }}</label>
                            @error('form.party.start_date') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                        <div class="form-group datepicker-wrapper relative w-full">
                            <input wire:model="form.end_date" type="text" name="end_date" id="end_date" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                            <label for="end_date" class="wrapped-label">{{ __('forms.contract.endDateContract') }}</label>
                            @error('form.party.end_date') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                        </div>
        </fieldset>

        {{-- Payment Information --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2>{{ __('forms.paymentDetails') }}</h2>
            </legend>
            <p class="text-sm text-black mb-4">{{ __('forms.contract.nszu_payment_account') }}</p>
            <div class="form-row-3">
                <div class="form-group">
                    <input wire:model="form.contractor_payment_details.bank_name"  type="text" name="bank_name" id="bank_name" class="peer input" placeholder=" " required />
                    <label for="bank_name" class="label">{{ __('forms.bankName') }}</label>
                    @error('form.party.bank_name') <p class="text-error">{{$message}}</p> @enderror
                </div>
                <div class="form-group">
                    <input wire:model="form.contractor_payment_details.bank_name"  type="text" name="MFO" id="MFO" class="peer input" placeholder=" " required />
                    <label for="MFO" class="label">{{ __('forms.mfo') }}</label>
                    @error('form.party.MFO') <p class="text-error">{{$message}}</p> @enderror
                </div>
            </div>
            <div class="form-row-3">
                <div class="form-group">
                    <input
                        required
                        type="text"
                        placeholder=" "
                        class="peer input"
                        wire:model="form.contractor_payment_details.payer_account"
                        x-data
                        x-mask="UA99 9999999 999999999999999999"
                    />
                    <label class="label">{{ __('forms.payerAccount') }}</label>
                    @error('form.contractor_payment_details.payer_account')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>

        {{-- Places of service provision --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2> {{ __('forms.placesOfService') }}</h2>
            </legend>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                {{ __('Мiсце надання медичних послуг - фактична адреса провадження надавачем господарської дiяльностi з медичної практики, де особам, на яких розповсюджуються державнi гарантiї медичного обслуговування населення вiдно до Закону України "Про державнi фiнансовi гарантiї медичного обслуговування населення", надаватимуться медичнi послуги. У разi наявностi декiлькох мiсць надання медичних послуг, iнформацiя про такi мiсця зазначається окремо щодо кожного мiсця.') }}
            </p>

            <div class="form-row-3">
                <div class="form-group group">
                    <select wire:model="divisionFilter"
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
                </div>

            </div>

            <div class="form-group mt-4">
                <button
                    type="button"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition duration-150 ease-in-out"
                    wire:click.prevent="addPlaceOfService"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    {{ __('Додати місце надання медичних послуг') }}
                </button>
            </div>
        </fieldset>

        <div class="overflow-x-auto relative">
            <fieldset class="fieldset" id="section-external-contractors"
                      x-data="{
            openModal: false,
            modalParty: { legalEntity: '', contractNumber: '', issuedAt: '', expiresAt: '' },
        }"
            >
                <legend class="legend">
                    <h2>{{ __('forms.involvedPersons') }}</h2>
                </legend>

                <table class="table-input w-inherit">
                    <thead class="thead-input">
                    <tr>
                        <th scope="col" class="td-input">{{ __('forms.legalEntity') }}</th>
                        <th scope="col" class="td-input">{{ __('forms.externalContractorNumber') }}</th>
                        <th scope="col" class="td-input">{{ __('forms.externalContractorIssuedAt') }}</th>
                        <th scope="col" class="td-input">{{ __('forms.externalContractorExpiresAt') }}</th>
                        <th scope="col" class="td-input">{{ __('forms.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(isset($external_contractors) && is_array($external_contractors))
                        @foreach($external_contractors as $key => $external_contractor)
                            <tr>
                                <td class="td-input">
                                    {{ $external_contractor['legal_entity']['name'] ?? '' }}
                                </td>
                                <td class="td-input">
                                    {{ $external_contractor['contract']['number'] ?? '' }}
                                </td>
                                <td class="td-input">
                                    {{ $external_contractor['contract']['issued_at'] ?? '' }}
                                </td>
                                <td class="td-input">
                                    {{ $external_contractor['contract']['expires_at'] ?? '' }}
                                </td>
                                <td class="td-input flex flex-row gap-2">
                                    <button wire:click.prevent="editExternalContractors({{$key}})" class="svg-hover-action">
                                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"></path>
                                        </svg>
                                    </button>

                                    <button wire:click.prevent="deleteExternalContractors({{$key}})" class="svg-hover-action">
                                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m-4-8v8m-4-8v8h14m-12 4h10m-10 0a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-10Zm3-11V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2m-4-2h4"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>

                <button
                    type="button"
                    class="item-add my-5"
                    @click="openModal = true; modalParty = { legalEntity: '', contractNumber: '', issuedAt: '', expiresAt: '' }"
                >
                    <span>{{ __('forms.addInvolvedPerson') }}</span>
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
                                 class="modal-content h-fit w-full max-w-6xl rounded-2xl shadow-lg bg-white"
                            >
                                <h3 class="modal-header" :id="$id('modal-title')">
                                    {{ __('forms.addInvolvedPerson') }}
                                </h3>

                                <form>
                                    <div class="form-row-modal">
                                        <div>
                                            <label for="legalEntity" class="label-modal">{{__('forms.legalEntity')}}<span class="text-red-600"> *</span></label>
                                            <input
                                                x-model="modalParty.legalEntity"
                                                type="text"
                                                id="legalEntity"
                                                class="input-modal"
                                                required
                                            >
                                        </div>

                                        <div>
                                            <label for="contractNumber" class="label-modal">{{__('forms.externalContractorNumber')}}<span class="text-red-600"> *</span></label>
                                            <input
                                                x-model="modalParty.contractNumber"
                                                type="text"
                                                id="contractNumber"
                                                class="input-modal"
                                                required
                                            >
                                        </div>

                                        <div class="relative">
                                            <svg class="svg-input absolute left-1 !top-2/3 transform -translate-y-1/2 pointer-events-none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7a2 2 0 0 1 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm5-6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z" clip-rule="evenodd"/>
                                            </svg>
                                            <label for="issuedAt" class="label-modal">{{__('forms.externalContractorIssuedAt')}}<span class="text-red-600"> *</span></label>
                                            <input
                                                x-model="modalParty.issuedAt"
                                                type="text"
                                                id="issuedAt"
                                                class="input-modal datepicker-input"
                                                autocomplete="off"
                                                required
                                            >
                                        </div>

                                        <div class="relative">
                                            <svg class="svg-input absolute left-1 !top-2/3 transform -translate-y-1/2 pointer-events-none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7a2 2 0 0 1 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm5-6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z" clip-rule="evenodd"/>
                                            </svg>
                                            <label for="expiresAt" class="label-modal">{{__('forms.externalContractorExpiresAt')}}</label>
                                            <input
                                                x-model="modalParty.expiresAt"
                                                type="text"
                                                id="expiresAt"
                                                class="input-modal datepicker-input"
                                                autocomplete="off"
                                            >
                                        </div>
                                    </div>

                                    <p class="text-sm text-gray-400 mb-2">{{ __('forms.form_required_note') }}</p>

                                    <div class="mt-6 flex flex-row items-center gap-4 border-t border-gray-200 pt-6">
                                        <button type="button"
                                                @click="openModal = false"
                                                class="button-minor"
                                        >
                                            {{__('forms.cancel')}}
                                        </button>

                                        <button type="submit"
                                                @click.prevent="$wire.addExternalContractor(modalParty); openModal = false"
                                                :class="{ 'opacity-50 cursor-not-allowed': !(modalParty.legalEntity && modalParty.contractNumber && modalParty.issuedAt) }"
                                                :disabled="!(modalParty.legalEntity && modalParty.contractNumber && modalParty.issuedAt)"
                                                class="button-primary"
                                        >
                                            {{__('forms.save')}}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </template>

            </fieldset>
        </div>

        {{-- Block 2: Legal Entity Documents --}}
        {{-- This section handles the file uploads for the contract. --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2>{{ __('forms.documentsMedicalOrganization') }}</h2>
            </legend>
            <div class='grid grid-cols-1 gap-9 sm:grid-cols-2'>
                <div class='flex flex-col gap-5.5'>
                    <x-forms.form-group>
                        <x-slot name='label'>
                            <label for='statute_md5' class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('forms.statuteMd5') }} *</label>
                        </x-slot>
                        <x-slot name='input'>
                            <input
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                                id="statute_md5"
                                type="file"
                                wire:model='form.statute_md5'
                            >
                        </x-slot>
                        @error('form.statute_md5')
                        <x-forms.error>{{ $message }}</x-forms.error>
                        @enderror
                    </x-forms.form-group>
                </div>
                <div class='flex flex-col gap-5.5'>
                    <x-forms.form-group>
                        <x-slot name='label'>
                            <label for='additional_document_md5' class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('forms.additionalDocumentMd5') }} *</label>
                        </x-slot>
                        <x-slot name='input'>
                            <input
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                                id="additional_document_md5"
                                type="file"
                                wire:model='form.additional_document_md5'
                            >
                        </x-slot>
                        @error('form.additional_document_md5')
                        <x-forms.error>{{ $message }}</x-forms.error>
                        @enderror
                    </x-forms.form-group>
                </div>
            </div>
        </fieldset>

        {{-- Agreement --}}
        <div class='w-full mt-4 bg-white border-t border-gray-200 dark:border-gray-700'>
            <div class='flex flex-col gap-9'>
                <div class='dark:bg-boxdark'>
                    <div class='border-stroke px-6.5 py-1 dark:border-strokedark'>
                        <h3 class='font-medium text-black dark:text-white'>
                        </h3>
                    </div>

                    <div class='flex flex-col gap-5.5 p-6.5'>
                        <p class='ms-2 text-sm font-regular text-justify text-gray-900 dark:text-gray-300'>
                            {{ $dictionaries['CAPITATION_CONTRACT_CONSENT_TEXT']['APPROVED'] }}
                        </p>

                        <x-forms.form-group class='mt-4 pl-2'>
                            <x-slot name='input'>
                                <x-forms.checkbox
                                    wire:model='form.consent_text'
                                    id='consent_text'
                                    type='checkbox'
                                />
                                <label for='consent_text' class='ms-2 text-sm font-medium text-gray-900 dark:text-gray-300'>
                                    {{ __('forms.agree') }}
                                </label>
                            </x-slot>
                            @error('form.consent_text')
                            <x-slot name='error'>
                                <x-forms.error>
                                    {{ $message }}
                                </x-forms.error>
                            </x-slot>
                            @enderror
                        </x-forms.form-group>
                    </div>
                </div>
            </div>
        </div>

        <div class='mb-4.5 pt-10 flex flex-col gap-6 xl:flex-row justify-between items-center'>
            <x-secondary-button>
                <div class='xl:w-1/4 text-left'>
                    <a href="{{ route('contract.index', [legalEntity()]) }}">
                        {{ __('forms.back') }}
                    </a>
                </div>
            </x-secondary-button>

            <div class='xl:w-1/4 text-right'>
                <x-button
                    type='button'
                    wire:click='openModalSigned()'
                    class='text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800'
                >
                    {{ __('forms.sendForApproval') }}
                </x-button>
            </div>
        </div>

        <div wire:loading role='status' class='absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2'>
            <svg
                aria-hidden='true'
                class='w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600'
                viewBox='0 0 100 101'
                fill='none'
                xmlns='http://www.w3.org/2000/svg'
            >
                <path
                    d='M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z'
                    fill='currentColor'
                />
                <path
                    d='M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z'
                    fill='currentFill'
                />
            </svg>
        </div>
        @if($showModal == 'addExternalContractors')
            @include('livewire.contract._parts.modals._external_contractors')
        @endif
        @if($showModal == 'signed_content')
            @include('livewire.contract._parts.modals._modal_signed_content')
        @endif

    </div>
</div>
