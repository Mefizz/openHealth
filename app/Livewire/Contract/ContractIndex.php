<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Enums\Contract\Type;
use App\Models\Contract;
use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ContractIndex extends Component
{
    use FormTrait;
    use WithPagination;

    public array $typeFilter = [];

    public bool $isFiltersApplied = false;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->typeFilter = Type::values();
    }

    public function search(): void
    {
        $this->resetPage();
        $this->isFiltersApplied = true;
    }

    public function resetFilters(): void
    {
        $this->reset();
    }

    #[Computed]
    public function contracts(): LengthAwarePaginator
    {
        $query = Contract::whereLegalEntityId(legalEntity()->id);

        if ($this->isFiltersApplied) {
            //
        }

        return $query->paginate(config('pagination.per_page'));
    }

    public function render(): View
    {
        return view('livewire.contract.contract-index', ['contracts' => $this->contracts]);
    }
}
