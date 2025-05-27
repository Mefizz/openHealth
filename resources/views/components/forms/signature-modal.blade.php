<div x-cloak x-show="{{ $modalId }}" class="modal">
    <div class="modal-content" @click.away="{{ $modalId }} = false">
        <div class="modal-header">
            <h3>{{ __('forms.digital_signature') }}</h3>
            <button @click="{{ $modalId }} = false" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <div class="form-group group pb-4">
                <select
                    required
                    id="signKnedp"
                    wire:model="knedp"
                    class="input-select text-gray-800"
                >
                    <option value="" selected hidden>-- {{ __('forms.select_authority') }} --</option>
                    @foreach($certificateAuthorities as $ca)
                        <option value="{{ $ca['id'] }}">{{ $ca['name'] }}</option>
                    @endforeach
                </select>
                @error('knedp') <p class="text-error">{{ $message }}</p> @enderror
                <label for="signKnedp" class="label z-10">{{ __('forms.KNEDP') }}</label>
            </div>

            <div class="form-group group py-4">
                <input
                    type="file"
                    wire:model="keyContainer"
                    id="keyContainer"
                    accept=".p12,.pfx"
                    required
                />
                @error('keyContainer') <p class="text-error">{{ $message }}</p> @enderror
                <label for="keyContainer" class="label z-10">{{ __('forms.key_container') }}</label>
            </div>

            <div class="form-group group">
                <input
                    required
                    type="password"
                    placeholder=" "
                    id="signPassword"
                    wire:model="password"
                    class="input peer"
                />
                @error('password') <p class="text-error">{{ $message }}</p> @enderror
                <label for="signPassword" class="label z-10">{{ __('forms.password') }}</label>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="button-minor" @click="{{ $modalId }} = false">
                {{ __('forms.cancel') }}
            </button>
            <button
                type="button"
                class="button-primary"
                wire:click="{{ $submitMethod }}"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>{{ __('forms.sign_and_submit') }}</span>
                <span wire:loading>{{ __('forms.signing_in_progress') }}...</span>
            </button>
        </div>
    </div>
</div>
