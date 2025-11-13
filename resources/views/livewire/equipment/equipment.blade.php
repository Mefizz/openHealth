@use('App\Livewire\Equipment\EquipmentCreate')

<section class="section-form">
    <x-header-navigation x-data="{ showFilter: false }">
        <x-slot name="title">{{ __('equipments.new') }}</x-slot>
    </x-header-navigation>

    <div class="form" wire:key="{{ random_bytes(5) }}">

        @include('livewire.equipment.parts.main-data')
        @include('livewire.equipment.parts.additional-data', ['context' => 'create'])

        <div class="mt-6 flex flex-row items-center gap-4 pt-6">
            <div class="flex items-center space-x-3">
                <a href="{{ url()->previous() }}" class="button-minor">
                    {{ __('forms.cancel') }}
                </a>

                @if(get_class($this) === EquipmentCreate::class)
                    <button type="submit"
                            class="button-primary-outline flex items-center gap-2 px-4 py-2"
                            wire:click="createLocally"
                    >
                        <svg class="w-5 h-5"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2"
                        >
                            <path stroke-linejoin="round"
                                  d="M10 12v1h4v-1m4 7H6a1 1 0 0 1-1-1V9h14v9a1 1 0 0 1-1 1ZM4 5h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"
                            />
                        </svg>
                        {{ __('forms.save') }}
                    </button>
                @endif

                <button type="button" wire:click="create" class="button-primary">
                    {{ __('forms.save_and_send') }}
                </button>
            </div>
        </div>
    </div>

    <x-messages/>
    <x-forms.loading/>
</section>
