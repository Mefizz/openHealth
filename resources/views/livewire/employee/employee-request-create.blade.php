<div>
    <x-section-navigation class="">
        <x-slot name="title">{{ __('forms.add_employee') }}</x-slot>
        <x-slot name="navigation">
            {{-- Navigation content, if any --}}
        </x-slot>
    </x-section-navigation>

    <x-forms.form-wrapper class="relative" x-data="{
        showSignModal: $wire.entangle('showSignModal'),
        keyContainer: $wire.entangle('keyContainer'),
        password: $wire.entangle('password'),
        knedp: $wire.entangle('knedp'),
        certificateAuthorities: @js($certificateAuthorities)
    }">

        <form wire:submit.prevent="saveDraft" class="space-y-6">
            {{-- Personal Data --}}
            @include('livewire.employee._employee')

            {{-- Documents --}}
            @include('livewire.employee._documents')

            {{-- Education --}}
            @include('livewire.employee._education')

            {{-- Qualifications --}}
            @include('livewire.employee._qualifications')

            {{-- Specialities --}}
            {{-- @include('livewire.employee._specialities') --}} {{-- If you have this --}}

            {{-- Science Degree --}}
            @include('livewire.employee._science_degree')

            {{-- Digital Signature Section (New) --}}
            <fieldset class="fieldset">
                <legend class="legend">
                    <h2>{{ __('forms.digital_signature') }}</h2>
                </legend>

                <div class="form-row-2">
                    <div class="form-group group">
                        <x-forms.label for="knedp" class="default-label">
                            {{ __('forms.certificate_authority') }}
                        </x-forms.label>
                        <x-forms.select
                            wire:model.live="knedp"
                            id="knedp"
                            class="default-input @error('knedp') input-error @enderror"
                        >
                            <option value="">{{ __('forms.select_certificate_authority') }}</option>
                            <template x-for="ca in certificateAuthorities" :key="ca.code">
                                <option :value="ca.code" x-text="ca.description"></option>
                            </template>
                        </x-forms.select>
                        @error('knedp')
                        <p class="text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group group">
                        <x-forms.label for="keyContainer" class="default-label">
                            {{ __('forms.key_container_file') }}
                        </x-forms.label>
                        <input
                            wire:model="keyContainer"
                            type="file"
                            id="keyContainer"
                            class="input peer @error('keyContainer') input-error @enderror"
                            accept=".p7s,.cer,.pem,.p12,.jks,.zip,.dat" {{-- Specify accepted file types --}}
                        />
                        @error('keyContainer')
                        <p class="text-error">{{ $message }}</p>
                        @enderror
                        @if ($keyContainer)
                            <p class="text-sm text-gray-500 mt-2">{{ $keyContainer->getClientOriginalName() }}</p>
                        @endif
                    </div>
                </div>

                <div class="form-group group">
                    <x-forms.label for="password" class="default-label">
                        {{ __('forms.password') }}
                    </x-forms.label>
                    <input
                        wire:model="password"
                        type="password"
                        name="password"
                        id="password"
                        class="input peer @error('password') input-error @enderror"
                        placeholder=" "
                        required
                    />
                    @error('password')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </fieldset>

            {{-- Action Buttons --}}
            <div class="flex justify-end space-x-4">
                <button type="button" wire:click="saveDraft" class="button-minor">
                    {{ __('forms.save_draft') }}
                </button>
                <button type="button" wire:click="prepareForSigning" class="button-primary">
                    {{ __('forms.submit_for_approval') }}
                </button>
            </div>
            @error('signature')
            <p class="text-error text-center mt-4">{{ $message }}</p>
            @enderror
        </form>

        {{-- Signing Modal --}}
        <template x-if="showSignModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900 bg-opacity-50" role="dialog" aria-modal="true">
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 w-full max-w-md p-4">
                    <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('forms.confirm_submission') }}
                        </h3>
                        <button type="button" @click="showSignModal = false" class="text-gray-400 hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        </button>
                    </div>
                    <p class="mb-4 text-gray-700 dark:text-gray-300">{{ __('forms.confirm_submission_message') }}</p>
                    <div class="flex justify-end space-x-4">
                        <button type="button" @click="showSignModal = false" class="button-minor">
                            {{ __('forms.cancel') }}
                        </button>
                        <button type="button" wire:click="submitForApproval" class="button-primary" wire:loading.attr="disabled" wire:target="submitForApproval">
                            <span wire:loading.remove wire:target="submitForApproval">{{ __('forms.sign_and_submit') }}</span>
                            <span wire:loading wire:target="submitForApproval">
                                <svg class="inline mr-3 w-4 h-4 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.42463C69.6632 4.15243 63.5135 1.70014 57.1278 0.155495C50.7698 -1.33591 44.5028 0.841575 38.653 3.92161C32.8164 6.74501 27.6366 10.9702 23.4402 16.1437C19.2437 21.3172 16.0399 27.2745 13.9261 33.743C11.8123 40.2115 10.8711 47.011 11.1098 53.7906C11.3486 60.5702 12.7481 67.2472 15.2676 73.5412C17.787 79.8353 21.3732 85.6025 25.8676 90.5057C30.362 95.4089 35.6171 99.4077 41.3146 102.261C47.0121 105.114 53.0084 106.772 59.1009 107.13C65.1934 107.488 71.2185 106.53 77.0121 104.301C82.8056 102.072 88.2201 98.6698 92.9378 94.2097C97.6555 89.7495 101.528 84.4533 104.326 78.5024C107.124 72.5516 108.736 66.071 108.973 59.431C109.21 52.7911 108.066 46.1625 105.694 40.0975C103.322 34.0325 99.7711 28.5369 95.3409 23.9224C90.9107 19.3079 85.7423 15.6569 80.0963 13.0723C74.4503 10.4877 68.3758 8.94827 62.1374 8.52989C55.901 8.1115 49.6019 8.84752 43.606 10.6865C37.6101 12.5256 32.0988 15.4632 27.4239 19.349C22.749 23.2349 19.0663 27.9712 16.5936 33.2758C14.1209 38.5804 12.9238 44.3312 13.0694 50.1802Z" fill="currentColor"/>
                                </svg>
                                {{ __('forms.signing') }}...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </x-forms.form-wrapper>
</div>
