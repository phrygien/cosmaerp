<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Product;
use App\Models\Marque;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

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

    public $updatingProductId = null;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void          { $this->resetPage(); }
    public function updatedPerPage(): void         { $this->resetPage(); }
    public function updatedFilterState(): void     { $this->resetPage(); }
    public function updatedFilterMarque(): void
    {
        $this->resetPage();
        $this->filterCategorie = '';
    }
    public function updatedFilterCategorie(): void { $this->resetPage(); }

    #[On('product-created')]
    #[On('product-updated')]
    #[On('product-deleted')]
    #[On('parkod-imported')]
    public function refresh(): void
    {
        unset($this->products);
        unset($this->marquesList);
        unset($this->categoriesList);
        $this->resetPage();
    }

    public function edit($id): void
    {
        $this->dispatch('edit-product', id: $id);
    }

    public function confirmDelete($id): void
    {
        $this->dispatch('delete-product', id: $id);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'filterMarque', 'filterCategorie', 'perPage']);
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    public function toggleState($id): void
    {
        $this->updatingProductId = $id;

        try {
            DB::beginTransaction();

            $product  = Product::findOrFail($id);
            $newState = $product->state == 1 ? 0 : 1;

            $product->state = $newState;
            $product->save();

            DB::commit();

            unset($this->products);

            $this->dispatch('product-state-updated', id: $id, state: $newState);

            Flux::toast(
                heading: $newState == 1 ? 'Produit activé' : 'Produit désactivé',
                text: "Le produit \"{$product->designation}\" a été " . ($newState == 1 ? 'activé' : 'désactivé') . ' avec succès',
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier l'état du produit : " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            $this->updatingProductId = null;
        }
    }

    #[Computed]
    public function products()
    {
        if (empty(trim($this->search))) {
            return Product::query()
                ->with(['marque', 'categorie', 'type', 'ligne'])
                ->when($this->filterState !== '', fn($q) => $q->where('state', $this->filterState))
                ->when($this->filterMarque !== '', fn($q) => $q->where('marque_code', $this->filterMarque))
                ->when($this->filterCategorie !== '', fn($q) => $q->where('categorie_code', $this->filterCategorie))
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate($this->perPage);
        }

        $filters = collect();

        if ($this->filterState !== '') {
            $filters->push("state:={$this->filterState}");
        }

        if ($this->filterMarque !== '') {
            $filters->push("marque_code:={$this->filterMarque}");
        }

        if ($this->filterCategorie !== '') {
            $filters->push("categorie_code:={$this->filterCategorie}");
        }

        $sortMap = [
            'designation' => 'designation',
            'article'     => 'article',
            'type_id'     => 'type_id',
            'updated_at'  => 'updated_at',
        ];

        $sortField = $sortMap[$this->sortBy] ?? 'updated_at';

        return Product::search($this->search)
            ->options([
                'query_by'  => 'designation,designation_variant,article,EAN,ref_fabri_n_1,marque_nom,categorie_nom',
                'filter_by' => $filters->isNotEmpty() ? $filters->implode(' && ') : '',
                'sort_by'   => "{$sortField}:{$this->sortDirection}",
            ])
            ->query(fn($q) => $q->with(['marque', 'categorie', 'type', 'ligne']))
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
        return Category::query()
            ->with('marque')
            ->when($this->filterMarque !== '', fn($q) => $q->where('marque_code', $this->filterMarque))
            ->orderBy('name')
            ->get();
    }
};
?>

<div>
    <!-- Header -->
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce.400ms="search"
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

        <flux:modal.trigger name="importer-parkod">
            <flux:button variant="primary" class="w-full sm:w-auto" icon="arrow-up-on-square-stack">
                Importer un fichier PARKOD
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Filtres -->
    <div class="flex flex-col gap-3 mb-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <flux:radio.group wire:model.live="filterState" variant="segmented">
                <flux:radio label="Tous"    value=""  />
                <flux:radio label="Actif"   value="1" />
                <flux:radio label="Inactif" value="0" />
            </flux:radio.group>

            @if ($search || $filterState !== '' || $filterMarque !== '' || $filterCategorie !== '' || $perPage !== 10)
                <flux:button variant="danger" size="sm" wire:click="resetFilters" icon="arrow-path" class="w-full sm:w-auto">
                    Réinitialiser
                </flux:button>
            @endif
        </div>

        <!-- Filtres marque / catégorie -->
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

    <flux:card class="p-5">
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

                <flux:table.column class="hidden sm:table-cell">Marque</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Catégorie</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'type_id'"
                    :direction="$sortDirection"
                    wire:click="sort('type_id')"
                    class="hidden md:table-cell"
                >
                    Type
                </flux:table.column>

                <flux:table.column class="text-center">État</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row :key="$product->id" wire:key="product-{{ $product->id }}">

                        <!-- Article -->
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $product->article ?? $product->product_code }}
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
                                <div>État: {{ $product->state == 1 ? 'Actif' : 'Inactif' }}</div>
                            </div>
                        </flux:table.cell>

                        <!-- Marque -->
                        <flux:table.cell class="hidden sm:table-cell">
                            <span class="text-sm">{{ $product->marque?->name ?? '-' }}</span>
                        </flux:table.cell>

                        <!-- Catégorie -->
                        <flux:table.cell class="hidden sm:table-cell">
                            <span class="text-sm">{{ $product->categorie?->name ?? '-' }}</span>
                        </flux:table.cell>

                        <!-- Type -->
                        <flux:table.cell class="hidden md:table-cell">
                            <span class="text-sm">{{ $product->type?->name ?? '-' }}</span>
                        </flux:table.cell>

                        <!-- État -->
                        <flux:table.cell class="text-center">
                            <div class="flex items-center justify-center">
                                @if($updatingProductId === $product->id)
                                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                @else
                                    <button
                                        wire:click="toggleState({{ $product->id }})"
                                        type="button"
                                        role="switch"
                                        aria-checked="{{ $product->state == 1 ? 'true' : 'false' }}"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:opacity-80"
                                        style="background-color: {{ $product->state == 1 ? '#22c55e' : '#d1d5db' }}"
                                    >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        style="transform: translateX({{ $product->state == 1 ? '24px' : '4px' }})"
                                    />
                                    </button>
                                @endif
                            </div>
                            <span class="sr-only">{{ $product->state == 1 ? 'Actif' : 'Inactif' }}</span>
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

    </flux:card>
    <livewire:pages::products.parkod />
</div>
