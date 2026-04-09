<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\ProduitFournisseur;

new class extends Component
{
    use WithPagination;

    public int    $fournisseurId = 0;
    public string $sortBy        = 'id';
    public string $sortDirection = 'asc';
    public string $search        = '';
    public int    $perPage       = 10;

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

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

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
    public function produits()
    {
        return ProduitFournisseur::query()
            ->with(['product.marque', 'product.categorie'])
            ->where('fournisseur_id', $this->fournisseurId)
            ->when($this->search, fn($q) =>
            $q->whereHas('product', fn($p) =>
            $p->where('designation', 'like', "%{$this->search}%")
                ->orWhere('product_code', 'like', "%{$this->search}%")
                ->orWhere('article', 'like', "%{$this->search}%")
                ->orWhere('ref_fabri_n_1', 'like', "%{$this->search}%")
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
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher un produit..."
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

        <flux:modal.trigger name="create-produit-fournisseur">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Affecter un produit au fournisseur
            </flux:button>
        </flux:modal.trigger>
    </div>

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
            @forelse ($this->produits as $produit)
                <flux:table.row :key="$produit->id">

                    <!-- Produit -->
                    <flux:table.cell>
                        <p class="font-medium text-sm">
                            {{ $produit->product?->designation ?? '—' }}
                        </p>
                        <p class="text-xs text-zinc-400 mt-0.5">
                            {{ $produit->product?->product_code ?? '—' }}
                        </p>
                        <!-- Référence visible en mobile -->
                        <p class="text-xs text-zinc-400 mt-0.5 md:hidden">
                            Réf: {{ $produit->product?->ref_fabri_n_1 ?? '—' }}
                        </p>
                        <!-- Marque/catégorie visible en mobile -->
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
                        <!-- Taxe visible en mobile -->
                        <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                            Taxe: {{ $produit->tax ?? '—' }}%
                        </p>
                    </flux:table.cell>

                    <!-- Référence cachée en mobile -->
                    <flux:table.cell class="hidden md:table-cell text-zinc-400 text-sm">
                        {{ $produit->product?->ref_fabri_n_1 ?? '—' }}
                    </flux:table.cell>

                    <!-- Marque / Catégorie cachée en mobile/tablet -->
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

                    <!-- Taxe cachée en mobile -->
                    <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm">
                        {{ $produit->tax ?? '0' }}%
                    </flux:table.cell>

                    <!-- État -->
                    <flux:table.cell>
                        @if ($produit->state == 1)
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
                    <flux:table.cell colspan="7">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="shopping-bag" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                            <p class="text-zinc-400 font-medium text-sm">
                                @if ($search)
                                    Aucun produit trouvé pour "{{ $search }}"
                                @else
                                    Aucun produit associé à ce fournisseur
                                @endif
                            </p>
                            @if ($search)
                                <flux:button variant="ghost" size="sm" wire:click="$set('search', '')" class="mt-3">
                                    Réinitialiser la recherche
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::fournisseurs.product-fournisseur.create :fournisseur-id="$fournisseurId" />
</div>
