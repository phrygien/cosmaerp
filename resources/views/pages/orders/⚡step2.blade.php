<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Product;
use App\Models\DetailCommande;
use App\Models\Marque;
use App\Models\Category;
use App\Models\HistoriqueQuantiteDetailCommande;

new class extends Component
{
    use WithPagination;

    public int $commande_id;
    public string $search = '';
    public ?int $selectedProductId = null;
    public bool $showRepartitionModal = false;
    public bool $isEditMode      = false;
    public ?int $editingDetailId = null;

    public string $sortBy        = 'designation';
    public string $sortDirection = 'asc';

    // ── Filtres avancés ──────────────────────────────────────────────────
    public bool   $showAdvancedFilters = false;
    public string $filterMarque        = '';
    public string $filterCategorie     = '';
    public string $filterStatut        = '';

    // ── Historique ───────────────────────────────────────────────────────
    public string $filterHistoType = ''; // '' | 'new' | 'added' | 'reduced'

    public function updatingSearch(): void
    {
        $this->resetPage();
        unset($this->products);
    }

    public function updatingFilterMarque(): void
    {
        $this->resetPage();
        unset($this->products);
    }

    public function updatingFilterCategorie(): void
    {
        $this->resetPage();
        unset($this->products);
    }

    public function updatingFilterStatut(): void
    {
        $this->resetPage();
        unset($this->products);
    }

    public function updatingFilterHistoType(): void
    {
        $this->resetPage();
        unset($this->historiques);
    }

    public function resetAdvancedFilters(): void
    {
        $this->filterMarque    = '';
        $this->filterCategorie = '';
        $this->filterStatut    = '';
        $this->resetPage();
        unset($this->products);
    }

    public function toggleAdvancedFilters(): void
    {
        $this->showAdvancedFilters = ! $this->showAdvancedFilters;
    }

    #[Computed]
    public function marques()
    {
        return Marque::orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }

        unset($this->products);
    }

    #[Computed(cache: false)]
    public function products()
    {
        $addedIds     = $this->selectedProductIds;
        $filterStatut = $this->filterStatut;

        if (empty(trim($this->search))) {
            return Product::query()
                ->where('state', 1)
                ->with(['marque', 'categorie'])
                ->when($this->filterMarque !== '',    fn($q) => $q->where('marque_code', $this->filterMarque))
                ->when($this->filterCategorie !== '', fn($q) => $q->where('categorie_code', $this->filterCategorie))
                ->when($filterStatut === 'added',     fn($q) => $q->whereIn('id', $addedIds))
                ->when($filterStatut === 'available', fn($q) => $q->whereNotIn('id', $addedIds))
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(25);
        }

        $filters = collect(['state:=1']);

        if ($this->filterMarque !== '') {
            $filters->push("marque_id:={$this->filterMarque}");
        }

        if ($this->filterCategorie !== '') {
            $filters->push("categorie_id:={$this->filterCategorie}");
        }

        $sortMap = [
            'designation'  => 'designation',
            'product_code' => 'product_code',
            'ean'          => 'ean',
            'updated_at'   => 'updated_at',
        ];

        $sortField = $sortMap[$this->sortBy] ?? 'designation';

        return Product::search($this->search)
            ->options([
                'query_by'  => 'designation,designation_variant,product_code,article,EAN',
                'filter_by' => $filters->implode(' && '),
                'sort_by'   => "{$sortField}:{$this->sortDirection}",
            ])
            ->query(fn($q) => $q
                ->with(['marque', 'categorie'])
                ->when($filterStatut === 'added',     fn($q) => $q->whereIn('id', $addedIds))
                ->when($filterStatut === 'available', fn($q) => $q->whereNotIn('id', $addedIds))
            )
            ->paginate(15);
    }

    #[Computed]
    public function details()
    {
        return DetailCommande::where('commande_id', $this->commande_id)
            ->with(['product.marque', 'destinations.magasin'])
            ->get();
    }

    #[Computed]
    public function selectedProductIds(): array
    {
        return $this->details->pluck('product_id')->toArray();
    }

    #[Computed(cache: false)]
    public function historiques()
    {
        return HistoriqueQuantiteDetailCommande::query()
            ->where('commande_id', $this->commande_id)
            ->with(['product', 'user'])
            ->when($this->filterHistoType === 'new',     fn($q) => $q->whereNull('ancienne_quantite'))
            ->when($this->filterHistoType === 'added',   fn($q) => $q->whereNotNull('ancienne_quantite')->whereColumn('nouvelle_quantite', '>', 'ancienne_quantite'))
            ->when($this->filterHistoType === 'reduced', fn($q) => $q->whereNotNull('ancienne_quantite')->whereColumn('nouvelle_quantite', '<', 'ancienne_quantite'))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function historiquesCount(): int
    {
        return HistoriqueQuantiteDetailCommande::where('commande_id', $this->commande_id)->count();
    }

    public function openRepartition(int $productId): void
    {
        if (in_array($productId, $this->selectedProductIds)) {
            $this->dispatch('notify', type: 'warning', message: 'Ce produit est déjà dans la commande.');
            return;
        }

        $this->selectedProductId    = $productId;
        $this->isEditMode           = false;
        $this->editingDetailId      = null;
        $this->showRepartitionModal = true;
    }

    public function editRepartition(int $detailId): void
    {
        $detail = DetailCommande::find($detailId);

        if (! $detail || $detail->commande_id !== $this->commande_id) {
            return;
        }

        $this->selectedProductId    = $detail->product_id;
        $this->editingDetailId      = $detailId;
        $this->isEditMode           = true;
        $this->showRepartitionModal = true;
    }

    public function closeModal(): void
    {
        $this->showRepartitionModal = false;
        $this->selectedProductId    = null;
        $this->isEditMode           = false;
        $this->editingDetailId      = null;
    }

    #[On('repartition-saved')]
    public function onRepartitionSaved(): void
    {
        $this->closeModal();
        unset($this->details, $this->selectedProductIds, $this->products, $this->historiques, $this->historiquesCount);
    }

    public function removeDetail(int $detailId): void
    {
        $detail = DetailCommande::find($detailId);

        if ($detail && $detail->commande_id === $this->commande_id) {
            $detail->destinations()->delete();
            $detail->delete();
            unset($this->details, $this->selectedProductIds, $this->products);
        }
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return (int)($this->filterMarque !== '')
            + (int)($this->filterCategorie !== '')
            + (int)($this->filterStatut !== '');
    }
};
?>

<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Panneau gauche avec onglets Alpine ── --}}
        <div class="lg:col-span-2 space-y-4" x-data="{ activeTab: 'catalogue' }">

            {{-- Barre d'onglets --}}
            <div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-700">

                <button
                    @click="activeTab = 'catalogue'"
                    :class="activeTab === 'catalogue'
                        ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border-b-2 border-transparent'"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                    </svg>
                    Catalogue
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        {{ $this->products->total() }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'historique'"
                    :class="activeTab === 'historique'
                        ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border-b-2 border-transparent'"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Historique
                    @if($this->historiquesCount > 0)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                            {{ $this->historiquesCount }}
                        </span>
                    @endif
                </button>

            </div>

            {{-- ══ Panneau Catalogue ══ --}}
            <div x-show="activeTab === 'catalogue'" class="space-y-4">

                {{-- Barre de recherche + bouton filtres avancés --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="Rechercher par désignation, code, article, EAN…"
                            icon="magnifying-glass"
                            clearable
                        />
                    </div>

                    <flux:button
                        wire:click="toggleAdvancedFilters"
                        variant="{{ $this->activeFiltersCount > 0 ? 'primary' : 'ghost' }}"
                        icon="adjustments-horizontal"
                        class="shrink-0"
                    >
                        Filtres
                        @if($this->activeFiltersCount > 0)
                            <flux:badge size="sm" color="white" class="ml-1">
                                {{ $this->activeFiltersCount }}
                            </flux:badge>
                        @endif
                    </flux:button>
                </div>

                {{-- Panneau filtres avancés --}}
                @if($showAdvancedFilters)
                    <div
                        x-data
                        x-show="true"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4 space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Filtres avancés
                            </p>
                            @if($this->activeFiltersCount > 0)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    wire:click="resetAdvancedFilters"
                                    icon="x-mark"
                                    class="text-gray-400 hover:text-red-500"
                                >
                                    Réinitialiser
                                </flux:button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                            <div>
                                <flux:label class="mb-1">Marque</flux:label>
                                <flux:select wire:model.live="filterMarque" size="sm">
                                    <flux:select.option value="">Toutes les marques</flux:select.option>
                                    @foreach($this->marques as $marque)
                                        <flux:select.option value="{{ $marque->code }}">{{ $marque->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div>
                                <flux:label class="mb-1">Catégorie</flux:label>
                                <flux:select wire:model.live="filterCategorie" size="sm">
                                    <flux:select.option value="">Toutes les catégories</flux:select.option>
                                    @foreach($this->categories as $cat)
                                        <flux:select.option value="{{ $cat->code }}">{{ $cat->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div>
                                <flux:label class="mb-1">Statut dans la commande</flux:label>
                                <flux:select wire:model.live="filterStatut" size="sm">
                                    <flux:select.option value="">Tous</flux:select.option>
                                    <flux:select.option value="added">Déjà ajoutés</flux:select.option>
                                    <flux:select.option value="available">Disponibles</flux:select.option>
                                </flux:select>
                            </div>

                        </div>
                    </div>
                @endif

                {{-- Table produits --}}
                <flux:table :paginate="$this->products" container:class="max-h-[600px]">
                    <flux:table.columns sticky class="bg-white dark:bg-zinc-900 p-3">
                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'product_code'"
                            :direction="$sortDirection"
                            wire:click="sort('product_code')"
                        >
                            Code
                        </flux:table.column>

                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'designation'"
                            :direction="$sortDirection"
                            wire:click="sort('designation')"
                        >
                            Désignation
                        </flux:table.column>

                        <flux:table.column
                            sortable
                            :sorted="$sortBy === 'ean'"
                            :direction="$sortDirection"
                            wire:click="sort('ean')"
                        >
                            EAN
                        </flux:table.column>

                        <flux:table.column>Marque</flux:table.column>
                        <flux:table.column>Catégorie</flux:table.column>
                        <flux:table.column>Statut</flux:table.column>
                        <flux:table.column>Action</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->products as $product)
                            @php $isSelected = in_array($product->id, $this->selectedProductIds); @endphp

                            <flux:table.row :key="$product->id">

                                <flux:table.cell>
                                    <span class="font-mono text-xs text-gray-500">{{ $product->product_code }}</span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">
                                        {{ $product->designation }}
                                    </p>
                                    @if($product->designation_variant)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $product->designation_variant }}</p>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($product->EAN)
                                        <span class="font-mono text-xs text-gray-500 tracking-wider">{{ $product->EAN }}</span>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($product->marque)
                                        <flux:badge size="sm" color="blue" inset="top bottom">{{ $product->marque->name }}</flux:badge>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($product->categorie)
                                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $product->categorie->name }}</flux:badge>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($isSelected)
                                        <flux:badge size="sm" color="green" inset="top bottom" icon="check">Ajouté</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="zinc" inset="top bottom">Disponible</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if($isSelected)
                                        @php $detail = $this->details->firstWhere('product_id', $product->id); @endphp
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            inset="top bottom"
                                            class="text-indigo-500 hover:text-indigo-700"
                                            wire:click="editRepartition({{ $detail->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="editRepartition({{ $detail->id }})"
                                        >
                                            <i class="hgi-stroke hgi-pencil-edit-01"></i>
                                            Modifier
                                        </flux:button>
                                    @else
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            inset="top bottom"
                                            wire:click="openRepartition({{ $product->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="openRepartition({{ $product->id }})"
                                        >
                                            <i class="hgi-stroke hgi-add-circle"></i>
                                            Ajouter
                                        </flux:button>
                                    @endif
                                </flux:table.cell>

                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="py-12 text-center text-gray-400">
                                    Aucun produit trouvé
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

            </div>

            {{-- ══ Panneau Historique ══ --}}
            <div x-show="activeTab === 'historique'" class="space-y-4">

                {{-- Filtres --}}
                <div class="flex flex-wrap gap-2">
                    <flux:button
                        size="sm"
                        variant="{{ $filterHistoType === '' ? 'primary' : 'ghost' }}"
                        wire:click="$set('filterHistoType', '')"
                    >
                        Tous
                    </flux:button>
                    <flux:button
                        size="sm"
                        variant="{{ $filterHistoType === 'new' ? 'primary' : 'ghost' }}"
                        wire:click="$set('filterHistoType', 'new')"
                    >
                        Ajouts
                    </flux:button>
                    <flux:button
                        size="sm"
                        variant="{{ $filterHistoType === 'added' ? 'primary' : 'ghost' }}"
                        wire:click="$set('filterHistoType', 'added')"
                    >
                        Augmentations
                    </flux:button>
                    <flux:button
                        size="sm"
                        variant="{{ $filterHistoType === 'reduced' ? 'primary' : 'ghost' }}"
                        wire:click="$set('filterHistoType', 'reduced')"
                    >
                        Réductions
                    </flux:button>
                </div>

                {{-- Table historique --}}
                <flux:table :paginate="$this->historiques">
                    <flux:table.columns>
                        <flux:table.column>Produit</flux:table.column>
                        <flux:table.column>Modification</flux:table.column>
                        <flux:table.column>Quantité</flux:table.column>
                        <flux:table.column>Motif</flux:table.column>
                        <flux:table.column>Utilisateur</flux:table.column>
                        <flux:table.column>Date</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->historiques as $item)
                            @php
                                $isNew   = is_null($item->ancienne_quantite);
                                $isAdded = !$isNew && $item->nouvelle_quantite > $item->ancienne_quantite;
                                $diff    = $isNew
                                    ? $item->nouvelle_quantite
                                    : abs($item->nouvelle_quantite - $item->ancienne_quantite);
                            @endphp

                            <flux:table.row :key="$item->id" wire:key="histo-{{ $item->id }}">

                                {{-- Produit --}}
                                <flux:table.cell>
                                    <p class="font-medium text-gray-900 dark:text-white text-sm truncate max-w-[180px]">
                                        {{ $item->product->designation ?? '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400 font-mono">
                                        {{ $item->product->product_code ?? '' }}
                                    </p>
                                </flux:table.cell>

                                {{-- Type de modification --}}
                                <flux:table.cell>
                                    @if($isNew)
                                        <flux:badge size="sm" color="blue" inset="top bottom">
                                            Nouveau
                                        </flux:badge>
                                    @elseif($isAdded)
                                        <flux:badge size="sm" color="green" inset="top bottom">
                                            Augmentation
                                        </flux:badge>
                                    @else
                                        <flux:badge size="sm" color="orange" inset="top bottom">
                                            Réduction
                                        </flux:badge>
                                    @endif
                                </flux:table.cell>

                                {{-- Quantité avant → après --}}
                                <flux:table.cell>
                                    @if($isNew)
                                        <span class="font-mono text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                            {{ $item->nouvelle_quantite }}
                                        </span>
                                    @else
                                        <span class="flex items-center gap-1.5 font-mono text-sm tabular-nums">
                                            <span class="text-gray-400">{{ $item->ancienne_quantite }}</span>
                                            <svg class="size-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            </svg>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $item->nouvelle_quantite }}</span>
                                            <span class="{{ $isAdded ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400' }} text-xs">
                                                ({{ $isAdded ? '+' : '−' }}{{ $diff }})
                                            </span>
                                        </span>
                                    @endif
                                </flux:table.cell>

                                {{-- Motif --}}
                                <flux:table.cell>
                                    @if($item->motif)
                                        <span class="text-xs text-gray-500 italic">{{ $item->motif }}</span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </flux:table.cell>

                                {{-- Utilisateur --}}
                                <flux:table.cell>
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $item->user->name ?? 'Système' }}
                                    </span>
                                </flux:table.cell>

                                {{-- Date --}}
                                <flux:table.cell class="whitespace-nowrap">
                                    <span class="text-xs text-gray-500" title="{{ $item->created_at->format('d/m/Y H:i') }}">
                                        {{ $item->created_at->diffForHumans() }}
                                    </span>
                                </flux:table.cell>

                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="py-12 text-center text-gray-400">
                                    <svg class="size-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                    Aucune modification enregistrée
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

            </div>

        </div>

        {{-- ── Récapitulatif ── --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Récapitulatif</flux:heading>
                <flux:badge color="indigo">{{ $this->details->count() }} article(s)</flux:badge>
            </div>

            @if($this->details->isEmpty())
                <div class="rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 py-12 text-center text-gray-400">
                    <svg class="size-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                    </svg>
                    <p class="text-sm">Aucun produit sélectionné</p>
                </div>
            @else
                <div class="space-y-3 max-h-[90vh] overflow-y-auto pr-1">
                    @foreach($this->details as $detail)
                        <div
                            wire:key="detail-{{ $detail->id }}"
                            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $detail->product->designation }}
                                    </p>
                                    <p class="text-xs text-gray-400 font-mono">
                                        {{ $detail->product->product_code }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        inset="top bottom"
                                        class="text-indigo-400 hover:text-indigo-600"
                                        wire:click="editRepartition({{ $detail->id }})"
                                    >
                                        <i class="hgi-stroke hgi-pencil-edit-01"></i>
                                    </flux:button>
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        inset="top bottom"
                                        class="text-red-400 hover:text-red-600"
                                        wire:click="removeDetail({{ $detail->id }})"
                                        wire:confirm="Supprimer ce produit de la commande ?"
                                    >
                                        <i class="hgi-stroke hgi-delete-02"></i>
                                    </flux:button>
                                </div>
                            </div>

                            <div class="mt-2 space-y-1">
                                @foreach($detail->destinations as $dest)
                                    <div class="flex items-center justify-between text-xs text-rose-900 font-bold dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <svg class="size-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                                            </svg>
                                            {{ $dest->magasin->name ?? '—' }}
                                        </span>
                                        <span class="font-semibold tabular-nums">{{ $dest->quantite }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-between text-xs font-semibold">
                                <span class="text-gray-500">Total</span>
                                <span class="text-gray-900 dark:text-white tabular-nums">
                                    {{ $detail->quantite }} unité(s)
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    {{-- ── Modal répartition ── --}}
    <flux:modal wire:model="showRepartitionModal" name="repartition-modal" class="w-full max-w-2xl">
        @if($showRepartitionModal && $selectedProductId)
            @livewire('pages::orders.repartition', [
                'commande_id' => $commande_id,
                'product_id'  => $selectedProductId,
                'detail_id'   => $editingDetailId,
                'edit_mode'   => $isEditMode,
            ], key('repartition-'.$selectedProductId.'-'.($editingDetailId ?? 'new')))
        @endif
    </flux:modal>
</div>
