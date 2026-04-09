<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Marque;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy        = 'name';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'asc';

    #[Url(as: 'q', except: '')]
    public string $search        = '';

    #[Url(as: 'etat', except: '')]
    public string $filterState   = '';

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedPerPage(): void     { $this->resetPage(); }
    public function updatedFilterState(): void { $this->resetPage(); }

    #[On('marque-created')]
    #[On('marque-updated')]
    #[On('marque-deleted')]
    public function refresh(): void
    {
        unset($this->marques);
        $this->resetPage();
    }

    public function edit(string $code): void
    {
        $this->dispatch('edit-marque', code: $code);
    }

    public function confirmDelete(string $code): void
    {
        $this->dispatch('delete-marque', code: $code);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'perPage']);
        $this->resetPage();
    }

    #[Computed]
    public function marques()
    {
        return Marque::query()
            ->withCount('categories')
            ->when($this->search,      fn($q) => $q->search($this->search))
            ->when($this->filterState !== '', fn($q) => $q->byState($this->filterState))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <!-- Header -->
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher une marque..."
                icon="magnifying-glass"
                class="w-full sm:w-72"
            />

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>

        <flux:modal.trigger name="create-marque">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter une marque
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Filtre état -->
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:radio.group wire:model.live="filterState" variant="segmented">
            <flux:radio label="Tous" value="" />
            <flux:radio label="Actif" value="1" />
            <flux:radio label="Inactif" value="0" />
        </flux:radio.group>

        @if ($search || $filterState !== '' || $perPage !== 10)
            <flux:button variant="danger" size="sm" wire:click="resetFilters" icon="arrow-path" class="w-full sm:w-auto">
                Réinitialiser
            </flux:button>
        @endif
    </div>

    <!-- Table -->
    <flux:table :paginate="$this->marques" variant="bordered">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'code'"
                :direction="$sortDirection"
                wire:click="sort('code')"
            >
                Code
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'name'"
                :direction="$sortDirection"
                wire:click="sort('name')"
            >
                Nom
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'state'"
                :direction="$sortDirection"
                wire:click="sort('state')"
            >
                État
            </flux:table.column>

            <flux:table.column class="hidden sm:table-cell">Catégories</flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->marques as $marque)
                <flux:table.row :key="$marque->code">

                    <!-- Code -->
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $marque->code }}
                        </flux:badge>
                    </flux:table.cell>

                    <!-- Nom -->
                    <flux:table.cell>
                        <p class="font-medium text-sm">{{ $marque->name }}</p>
                        <!-- Catégories visible uniquement en mobile -->
                        <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                            {{ $marque->categories_count }} catégorie{{ $marque->categories_count > 1 ? 's' : '' }}
                        </p>
                    </flux:table.cell>

                    <!-- État -->
                    <flux:table.cell>
                        @if ($marque->state == 1)
                            <flux:badge size="sm" color="green" inset="top bottom">Actif</flux:badge>
                        @else
                            <flux:badge size="sm" color="red" inset="top bottom">Inactif</flux:badge>
                        @endif
                    </flux:table.cell>

                    <!-- Catégories cachée en mobile -->
                    <flux:table.cell class="hidden sm:table-cell">
                        <flux:badge size="sm" color="blue" inset="top bottom">
                            {{ $marque->categories_count }} catégorie{{ $marque->categories_count > 1 ? 's' : '' }}
                        </flux:badge>
                    </flux:table.cell>

                    <!-- Actions -->
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit('{{ $marque->code }}')">
                                    Modifier
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $marque->code }}')">
                                    Supprimer
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>

            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="tag" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                            <p class="text-zinc-400 font-medium text-sm">
                                @if ($search || $filterState !== '')
                                    Aucune marque trouvée pour ces filtres
                                @else
                                    Aucune marque enregistrée
                                @endif
                            </p>
                            @if ($search || $filterState !== '')
                                <flux:button variant="ghost" size="sm" wire:click="resetFilters" class="mt-3">
                                    Réinitialiser les filtres
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::marques.create />
    <livewire:pages::marques.edit />
    <livewire:pages::marques.delete />
</div>
