@if($this->hasAlert())
    <div class="alert-message flex fixed top-[1.5rem] w-auto max-w-sm z-[100000] right-5">
        <div role="alert"
             class="relative p-4 pr-12 mb-4 text-sm rounded-lg shadow-lg
                    {{ $this->alertType === 'error' ? 'text-red-800 bg-red-50 dark:bg-gray-800 dark:text-red-400' : 'text-green-800 bg-green-50 dark:bg-gray-800 dark:text-green-400' }}">

            <span class="font-medium">{!! $this->alertMessage !!}</span>

            <button wire:click="dismissAlert" type="button" class="absolute top-1/2 right-4 -translate-y-1/2 font-bold text-xl" aria-label="Close">
                &times;
            </button>
        </div>
    </div>
@endif
