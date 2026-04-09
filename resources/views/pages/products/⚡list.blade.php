<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Product;
use App\Models\Marque;
use App\Models\Category;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy        = 'designation';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'asc';

    #[Url(as: 'q', except: '')]
    public string $search        = '';

    #[Url(as: 'etat', except: '')]
    public string $filterState   = '';

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    #[Url(as: 'marque', except: '')]
    public string $filterMarque  = '';

    #[Url(as: 'categorie', except: '')]
    public string $filterCategorie = '';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedPerPage(): void       { $this->resetPage(); }
    public function updatedFilterState(): void   { $this->resetPage(); }
    public function updatedFilterMarque(): void
    {
        $this->resetPage();
        $this->filterCategorie = ''; // Reset category when marque changes
    }
    public function updatedFilterCategorie(): void { $this->resetPage(); }

    #[On('product-created')]
    #[On('product-updated')]
    #[On('product-deleted')]
    public function refresh(): void
    {
        unset($this->products);
        unset($this->marquesList);
        unset($this->categoriesList);
        $this->resetPage();
    }

    public function edit(string $code): void
    {
        $this->dispatch('edit-product', code: $code);
    }

    public function confirmDelete(string $code): void
    {
        $this->dispatch('delete-product', code: $code);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'filterMarque', 'filterCategorie', 'perPage']);
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['marque', 'categorie', 'type', 'ligne'])
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('designation', 'like', '%' . $this->search . '%')
                        ->orWhere('designation_variant', 'like', '%' . $this->search . '%')
                        ->orWhere('article', 'like', '%' . $this->search . '%')
                        ->orWhere('EAN', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterState !== '', fn($q) => $q->where('state', $this->filterState))
            ->when($this->filterMarque !== '', fn($q) => $q->where('marque_code', $this->filterMarque))
            ->when($this->filterCategorie !== '', fn($q) => $q->where('categorie_code', $this->filterCategorie))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function marquesList()
    {
        return Marque::query()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categoriesList()
    {
        $query = Category::query()
            ->with('marque')
            ->orderBy('name');

        // Filtrer les catégories par marque sélectionnée
        if ($this->filterMarque !== '') {
            $query->where('marque_code', $this->filterMarque);
        }

        return $query->get();
    }
};
?>

<div>
    <!-- Header -->
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher un produit..."
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

        <flux:modal.trigger name="create-product">
            <flux:button variant="primary" class="w-full sm:w-auto" icon="arrow-up-on-square-stack">
                Importer PARKOD
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Filtres -->
    <div class="flex flex-col gap-3 mb-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <flux:radio.group wire:model.live="filterState" variant="segmented">
                <flux:radio label="Tous" value="" />
                <flux:radio label="Actif" value="1" />
                <flux:radio label="Inactif" value="0" />
            </flux:radio.group>

            @if ($search || $filterState !== '' || $filterMarque !== '' || $filterCategorie !== '' || $perPage !== 10)
                <flux:button variant="danger" size="sm" wire:click="resetFilters" icon="arrow-path" class="w-full sm:w-auto">
                    Réinitialiser
                </flux:button>
            @endif
        </div>

        <!-- Filtres supplémentaires -->
        <div class="flex flex-col gap-2 sm:flex-row">
            <flux:select wire:model.live="filterMarque" placeholder="Toutes les marques" class="w-full sm:w-64">
                <flux:select.option value="">Toutes les marques</flux:select.option>
                @foreach ($this->marquesList as $marque)
                    <flux:select.option value="{{ $marque->code }}">
                        {{ $marque->name }} ({{ $marque->code }})
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterCategorie" placeholder="Toutes les catégories" class="w-full sm:w-64">
                <flux:select.option value="">Toutes les catégories</flux:select.option>
                @foreach ($this->categoriesList as $categorie)
                    <flux:select.option value="{{ $categorie->code }}">
                        {{ $categorie->name }}
                        @if($categorie->marque)
                            ({{ $categorie->marque->name }})
                        @endif
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Table -->
    <flux:table :paginate="$this->products" variant="bordered">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'article'"
                :direction="$sortDirection"
                wire:click="sort('article')"
            >
                Article
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'designation'"
                :direction="$sortDirection"
                wire:click="sort('designation')"
            >
                Désignation
            </flux:table.column>

            <flux:table.column class="hidden sm:table-cell">
                Marque
            </flux:table.column>

            <flux:table.column class="hidden sm:table-cell">
                Catégorie
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'type_id'"
                :direction="$sortDirection"
                wire:click="sort('type_id')"
                class="hidden md:table-cell"
            >
                Type
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
            @forelse ($this->products as $product)
                <flux:table.row :key="$product->code">

                    <!-- Article -->
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $product->article ?? $product->code }}
                        </flux:badge>
                        @if($product->EAN)
                            <p class="text-xs text-zinc-400 mt-0.5">EAN: {{ $product->EAN }}</p>
                        @endif
                    </flux:table.cell>

                    <!-- Désignation -->
                    <flux:table.cell>
                        <p class="font-medium text-sm">{{ $product->designation }}</p>
                        @if($product->designation_variant)
                            <p class="text-xs text-zinc-400 mt-0.5">{{ $product->designation_variant }}</p>
                        @endif
                        @if($product->ref_fabri_n_1)
                            <p class="text-xs text-zinc-400 mt-0.5">Réf: {{ $product->ref_fabri_n_1 }}</p>
                        @endif
                        <!-- Infos mobiles -->
                        <div class="text-xs text-zinc-400 mt-1 sm:hidden">
                            <div>Marque: {{ $product->marque?->name ?? 'N/A' }}</div>
                            <div>Catégorie: {{ $product->categorie?->name ?? 'N/A' }}</div>
                            @if($product->type)
                                <div>Type: {{ $product->type->name ?? 'N/A' }}</div>
                            @endif
                        </div>
                    </flux:table.cell>

                    <!-- Marque (desktop) -->
                    <flux:table.cell class="hidden sm:table-cell">
                        <span class="text-sm">{{ $product->marque?->name ?? '-' }}</span>
                    </flux:table.cell>

                    <!-- Catégorie (desktop) -->
                    <flux:table.cell class="hidden sm:table-cell">
                        <span class="text-sm">{{ $product->categorie?->name ?? '-' }}</span>
                    </flux:table.cell>

                    <!-- Type (desktop) -->
                    <flux:table.cell class="hidden md:table-cell">
                        <span class="text-sm">{{ $product->type?->name ?? '-' }}</span>
                    </flux:table.cell>

                    <!-- État -->
                    <flux:table.cell>
                        @if ($product->state == 1)
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
                                <flux:menu.item icon="pencil" wire:click="edit('{{ $product->code }}')">
                                    Modifier
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $product->code }}')">
                                    Supprimer
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>

            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="cube" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                            <p class="text-zinc-400 font-medium text-sm">
                                @if ($search || $filterState !== '' || $filterMarque !== '' || $filterCategorie !== '')
                                    Aucun produit trouvé pour ces filtres
                                @else
                                    Aucun produit enregistré
                                @endif
                            </p>
                            @if ($search || $filterState !== '' || $filterMarque !== '' || $filterCategorie !== '')
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

</div>
