@php
    // тимчасово вимкнули перевірку, щоб завжди показувати меню
    $user = auth()->user();
    $hasActions = true;

    //    if ($user->can('view', $contract) || $user->can('update', $contract)) {
    //        $hasActions = true;
    //    }
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    {{-- Кнопка, яка відкриває/закриває меню --}}
    <button
        @click="@if($hasActions) open = !open @endif"
        class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white" type="button">
        {{-- Іконка "три крапки" --}}
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 4 15">
            <path d="M3.5 1.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6.041a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 5.959a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
        </svg>
    </button>

    {{-- Панель випадаючого меню --}}
    @if($hasActions)
        <div x-show="open" x-transition class="absolute right-0 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600" style="display: none;">
            <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">

                {{-- Дія "Переглянути" --}}
                {{-- @can('view', $contract) --}} {{-- Тимчасово вимкнено для розробки --}}
                <li>

                    <a href="{{ route('contract.show', ['legalEntity' => legalEntity(), 'contract' => $contract]) }}"--}}
                    class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        {{ __('forms.view') }}
                    </a>
                </li>
                {{-- @endcan --}}

                {{-- Дія "Редагувати" --}}
                {{-- @can('update', $contract) --}} {{-- Тимчасово вимкнено для розробки --}}
                <li>
                    <a href="{{ route('contract.edit', ['legalEntity' => legalEntity(), 'contract' => $contract]) }}"
                       class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/></svg>
                        {{ __('forms.edit') }}
                    </a>
                </li>
                {{-- @endcan --}}

            </ul>
        </div>
    @endif
</div>
