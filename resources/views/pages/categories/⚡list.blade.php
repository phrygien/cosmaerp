<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Category;
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

    #[Url(as: 'marque', except: '')]
    public string $filterMarque  = '';

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
    public function updatedFilterMarque(): void { $this->resetPage(); }

    #[On('category-created')]
    #[On('category-updated')]
    #[On('category-deleted')]
    public function refresh(): void
    {
        unset($this->categories);
        $this->resetPage();
    }

    public function edit(string $code): void
    {
        $this->dispatch('edit-category', code: $code);
    }

    public function confirmDelete(string $code): void
    {
        $this->dispatch('delete-category', code: $code);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'filterMarque', 'perPage']);
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->with('marque')
            ->when($this->search, fn($q) =>
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            )
            ->when($this->filterState !== '', fn($q) =>
            $q->where('state', $this->filterState)
            )
            ->when($this->filterMarque, fn($q) =>
            $q->where('marque_code', $this->filterMarque)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function marques()
    {
        return Marque::active()->orderBy('name')->get();
    }
};
?>

<div>
    <!-- Header -->
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher une catégorie..."
                icon="magnifying-glass"
                class="w-full sm:w-80"
            />

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>

        <flux:modal.trigger name="create-category">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter une catégorie
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Filtres -->
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">

            <!-- Filtre état -->
            <flux:radio.group wire:model.live="filterState" variant="segmented">
                <flux:radio label="Tous" value="" />
                <flux:radio label="Actif" value="1" />
                <flux:radio label="Inactif" value="0" />
            </flux:radio.group>

            <!-- Filtre marque -->
            <flux:select wire:model.live="filterMarque" class="w-full sm:w-48">
                <flux:select.option value="">Toutes les marques</flux:select.option>
                @foreach ($this->marques as $marque)
                    <flux:select.option value="{{ $marque->code }}">{{ $marque->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($search || $filterState !== '' || $filterMarque || $perPage !== 10)
            <flux:button variant="danger" size="sm" wire:click="resetFilters" icon="arrow-path" class="w-full sm:w-auto">
                Réinitialiser
            </flux:button>
        @endif
    </div>

    <!-- Table -->
    <flux:table :paginate="$this->categories" variant="bordered">
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

            <flux:table.column class="hidden sm:table-cell">
                Marque
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'state'"
                :direction="$sortDirection"
                wire:click="sort('state')"
            >
                État
            </flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
                <flux:table.row :key="$category->code">

                    <!-- Code -->
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $category->code }}
                        </flux:badge>
                    </flux:table.cell>

                    <!-- Nom -->
                    <flux:table.cell>
                        <p class="font-medium text-sm">{{ $category->name }}</p>
                        <!-- Marque visible en mobile uniquement -->
                        <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                            {{ $category->marque?->name ?? '—' }}
                        </p>
                    </flux:table.cell>

                    <!-- Marque cachée en mobile -->
                    <flux:table.cell class="hidden sm:table-cell">
                        @if ($category->marque)
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                {{ $category->marque->name }}
                            </flux:badge>
                        @else
                            <span class="text-zinc-400 text-sm">—</span>
                        @endif
                    </flux:table.cell>

                    <!-- État -->
                    <flux:table.cell>
                        @if ($category->state == 1)
                            <flux:badge size="sm" color="green" inset="top bottom">Actif</flux:badge>
                        @else
                            <flux:badge size="sm" color="red" inset="top bottom">Inactif</flux:badge>
                        @endif
                    </flux:table.cell>

                    <!-- Actions -->
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit('{{ $category->code }}')">
                                    Modifier
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $category->code }}')">
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
                                @if ($search || $filterState !== '' || $filterMarque)
                                    Aucune catégorie trouvée pour ces filtres
                                @else
                                    Aucune catégorie enregistrée
                                @endif
                            </p>
                            @if ($search || $filterState !== '' || $filterMarque)
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

    <livewire:pages::categories.create />
    <livewire:pages::categories.edit />
    <livewire:pages::categories.delete />
</div>
