<div>
    <x-section-navigation>
        <x-slot name="title">
            Перегляд договору №{{ $contract->contract_number }}
        </x-slot>
        <x-slot name="navigation">
            {{-- Кнопка для переходу на сторінку редагування --}}
            <a href="{{ route('contract.edit', ['legalEntity' => $legalEntity, 'contract' => $contract]) }}" class="button-primary">
                Редагувати
            </a>
        </x-slot>
    </x-section-navigation>

    <div class="bg-white p-6 md:p-8 rounded-lg shadow-sm">
        {{-- Тут ви можете вивести будь-яку інформацію про контракт у режимі "тільки для читання" --}}
        <div class="space-y-4">
            <div>
                <h3 class="font-semibold">Номер договору:</h3>
                <p>{{ $contract->contract_number }}</p>
            </div>
            <div>
                <h3 class="font-semibold">Статус:</h3>
                <p>{{ $contract->status }}</p>
            </div>
            <div>
                <h3 class="font-semibold">Дата початку:</h3>
                <p>{{ $contract->start_date }}</p>
            </div>
            <div>
                <h3 class="font-semibold">Дата завершення:</h3>
                <p>{{ $contract->end_date }}</p>
            </div>
        </div>
    </div>
</div>
