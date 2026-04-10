<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\ProduitFournisseur;
use App\Models\Marque;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public int    $fournisseurId   = 0;
    public string $sortBy          = 'id';
    public string $sortDirection   = 'asc';
    public string $search          = '';
    public int    $perPage         = 10;
    public string $filterMarque    = '';
    public string $filterCategorie = '';
    public string $filterEan       = '';
    public array $updatingStates = [];

    public function mount(int $fournisseurId): void
    {
        $this->fournisseurId = $fournisseurId;
    }

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
    public function updatedFilterMarque(): void    { $this->resetPage(); }
    public function updatedFilterCategorie(): void { $this->resetPage(); }
    public function updatedFilterEan(): void       { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->search          = '';
        $this->filterMarque    = '';
        $this->filterCategorie = '';
        $this->filterEan       = '';
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés',
            variant: 'info'
        );
    }

    public function toggleState(int $id): void
    {
        $this->updatingStates[$id] = true;

        try {
            DB::beginTransaction();

            $produitFournisseur = ProduitFournisseur::findOrFail($id);
            $oldState = $produitFournisseur->state;
            $newState = $oldState == 1 ? 0 : 1;

            $produitFournisseur->state = $newState;
            $produitFournisseur->save();

            DB::commit();

            unset($this->produits);

            $this->dispatch('produit-fournisseur-updated');

            Flux::toast(
                heading: $newState == 1 ? 'Produit activé' : 'Produit désactivé',
                text: "Le produit a été " . ($newState == 1 ? "activé" : "désactivé") . " pour ce fournisseur",
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier l'état: " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            unset($this->updatingStates[$id]);
        }
    }

    #[On('produit-fournisseur-created')]
    #[On('produit-fournisseur-updated')]
    #[On('produit-fournisseur-deleted')]
    public function refresh(): void
    {
        unset($this->produits);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->dispatch('edit-produit-fournisseur', id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-produit-fournisseur', id: $id);
    }

    #[Computed]
    public function marques()
    {
        return Marque::orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')
            ->when($this->filterMarque, fn($q) =>
            $q->where('marque_code', $this->filterMarque)
            )
            ->get();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->filterMarque !== '' || $this->filterCategorie !== '' || $this->filterEan !== '';
    }

    #[Computed]
    public function produits()
    {
        return ProduitFournisseur::query()
            ->with(['product.marque', 'product.categorie'])
            ->where('fournisseur_id', $this->fournisseurId)
            ->when($this->search, fn($q) =>
            $q->whereHas('product', fn($p) =>
            $p->where('designation',    'like', "%{$this->search}%")
                ->orWhere('product_code', 'like', "%{$this->search}%")
                ->orWhere('article',      'like', "%{$this->search}%")
                ->orWhere('ref_fabri_n_1','like', "%{$this->search}%")
                ->orWhere('EAN',          'like', "%{$this->search}%")
            )
            )
            ->when($this->filterMarque, fn($q) =>
            $q->whereHas('product', fn($p) =>
            $p->where('marque_code', $this->filterMarque)
            )
            )
            ->when($this->filterCategorie, fn($q) =>
            $q->whereHas('product', fn($p) =>
            $p->where('categorie_code', $this->filterCategorie)
            )
            )
            ->when($this->filterEan, fn($q) =>
            $q->whereHas('product', fn($p) =>
            $p->where('EAN', 'like', "%{$this->filterEan}%")
            )
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <!-- Header -->
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center flex-wrap">

            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher un produit..."
                icon="magnifying-glass"
                class="w-full sm:w-72"
            />

            <flux:select
                wire:model.live="filterMarque"
                class="w-full sm:w-44"
            >
                <flux:select.option value="">Toutes les marques</flux:select.option>
                @foreach ($this->marques as $marque)
                    <flux:select.option value="{{ $marque->code }}">
                        {{ $marque->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select
                wire:model.live="filterCategorie"
                class="w-full sm:w-44"
            >
                <flux:select.option value="">Toutes les catégories</flux:select.option>
                @foreach ($this->categories as $categorie)
                    <flux:select.option value="{{ $categorie->code }}">
                        {{ $categorie->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model.live.debounce="filterEan"
                placeholder="Filtrer par EAN..."
                icon="qr-code"
                class="w-full sm:w-48"
            />

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>

            @if($this->hasActiveFilters)
                <flux:button
                    variant="ghost"
                    size="sm"
                    wire:click="resetFilters"
                    class="!text-red-500"
                >
                    Réinitialiser
                </flux:button>
            @endif

        </div>

        <flux:modal.trigger name="create-produit-fournisseur">
            <flux:button variant="primary" class="w-full sm:w-auto shrink-0">
                Affecter un produit au fournisseur
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Badges filtres actifs -->
    @if($this->hasActiveFilters)
        <div class="flex flex-wrap gap-2 mb-3">
            @if($this->search)
                <flux:badge size="sm" color="blue" class="gap-1">
                    <flux:icon name="magnifying-glass" class="size-3" />
                    "{{ $this->search }}"
                </flux:badge>
            @endif
            @if($this->filterMarque)
                <flux:badge size="sm" color="blue" class="gap-1">
                    <flux:icon name="tag" class="size-3" />
                    {{ $this->marques->firstWhere('code', $this->filterMarque)?->name }}
                </flux:badge>
            @endif
            @if($this->filterCategorie)
                <flux:badge size="sm" color="zinc" class="gap-1">
                    <flux:icon name="squares-2x2" class="size-3" />
                    {{ $this->categories->firstWhere('code', $this->filterCategorie)?->name }}
                </flux:badge>
            @endif
            @if($this->filterEan)
                <flux:badge size="sm" color="purple" class="gap-1">
                    <flux:icon name="qr-code" class="size-3" />
                    EAN: {{ $this->filterEan }}
                </flux:badge>
            @endif
        </div>
    @endif

    <!-- Table -->
    <flux:table :paginate="$this->produits" variant="bordered">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortBy === 'product_id'"
                :direction="$sortDirection"
                wire:click="sort('product_id')"
            >
                Produit
            </flux:table.column>

            <flux:table.column class="hidden lg:table-cell">
                EAN
            </flux:table.column>

            <flux:table.column class="hidden md:table-cell">
                Référence
            </flux:table.column>

            <flux:table.column class="hidden lg:table-cell">
                Marque / Catégorie
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'prix_fournisseur_ht'"
                :direction="$sortDirection"
                wire:click="sort('prix_fournisseur_ht')"
            >
                Prix HT
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'tax'"
                :direction="$sortDirection"
                wire:click="sort('tax')"
                class="hidden sm:table-cell"
            >
                Taxe
            </flux:table.column>

            <flux:table.column class="text-center">
                État
            </flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->produits as $produit)
                <flux:table.row :key="$produit->id" wire:key="produit-{{ $produit->id }}">

                    <!-- Produit -->
                    <flux:table.cell>
                        <p class="font-medium text-sm">
                            {{ $produit->product?->designation ?? '—' }}
                        </p>
                        <p class="text-xs text-zinc-400 mt-0.5">
                            Code: {{ $produit->product?->product_code ?? '—' }}
                        </p>
                        <!-- EAN visible sur mobile -->
                        @if($produit->product?->EAN)
                            <p class="text-xs text-zinc-400 mt-0.5 lg:hidden">
                                EAN: {{ $produit->product->EAN }}
                            </p>
                        @endif
                        <p class="text-xs text-zinc-400 mt-0.5 md:hidden">
                            Réf: {{ $produit->product?->ref_fabri_n_1 ?? '—' }}
                        </p>
                        <div class="flex gap-1 mt-1 lg:hidden">
                            @if ($produit->product?->marque)
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $produit->product->marque->name }}
                                </flux:badge>
                            @endif
                            @if ($produit->product?->categorie)
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $produit->product->categorie->name }}
                                </flux:badge>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                            Taxe: {{ $produit->tax ?? '—' }}%
                        </p>
                    </flux:table.cell>

                    <!-- EAN -->
                    <flux:table.cell class="hidden lg:table-cell">
                        @if($produit->product?->EAN)
                            <flux:badge size="sm" color="purple" inset="top bottom" class="font-mono">
                                {{ $produit->product->EAN }}
                            </flux:badge>
                        @else
                            <span class="text-zinc-400 text-sm">—</span>
                        @endif
                    </flux:table.cell>

                    <!-- Référence -->
                    <flux:table.cell class="hidden md:table-cell text-zinc-400 text-sm">
                        {{ $produit->product?->ref_fabri_n_1 ?? '—' }}
                    </flux:table.cell>

                    <!-- Marque / Catégorie -->
                    <flux:table.cell class="hidden lg:table-cell">
                        <div class="flex flex-wrap gap-1">
                            @if ($produit->product?->marque)
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $produit->product->marque->name }}
                                </flux:badge>
                            @endif
                            @if ($produit->product?->categorie)
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $produit->product->categorie->name }}
                                </flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>

                    <!-- Prix HT -->
                    <flux:table.cell>
                        <span class="font-medium text-sm">
                            {{ number_format($produit->prix_fournisseur_ht, 2, ',', ' ') }} €
                        </span>
                    </flux:table.cell>

                    <!-- Taxe -->
                    <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm">
                        {{ $produit->tax ?? '0' }}%
                    </flux:table.cell>

                    <!-- État avec Toggle -->
                    <flux:table.cell class="text-center">
                        <div class="flex items-center justify-center">
                            @if(isset($updatingStates[$produit->id]))
                                <div class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            @else
                                <button
                                    wire:click="toggleState({{ $produit->id }})"
                                    type="button"
                                    role="switch"
                                    aria-checked="{{ $produit->state == 1 ? 'true' : 'false' }}"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:opacity-80"
                                    style="background-color: {{ $produit->state == 1 ? '#22c55e' : '#d1d5db' }}"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        style="transform: translateX({{ $produit->state == 1 ? '24px' : '4px' }})"
                                    />
                                </button>
                            @endif
                        </div>

                        <span class="sr-only">
                            {{ $produit->state == 1 ? 'Actif' : 'Inactif' }}
                        </span>
                    </flux:table.cell>

                    <!-- Actions -->
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit({{ $produit->id }})">
                                    Modifier
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $produit->id }})">
                                    Supprimer
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>

            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="shopping-bag" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                            <p class="text-zinc-400 font-medium text-sm">
                                @if($this->hasActiveFilters)
                                    Aucun produit ne correspond aux filtres sélectionnés.
                                @else
                                    Aucun produit associé à ce fournisseur
                                @endif
                            </p>
                            @if($this->hasActiveFilters)
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

    <livewire:pages::fournisseurs.product-fournisseur.create :fournisseur-id="$fournisseurId" />
    <livewire:pages::fournisseurs.product-fournisseur.edit />
</div>
