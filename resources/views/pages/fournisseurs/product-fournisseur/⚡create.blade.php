<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\ProduitFournisseur;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri', keep: false)]
    public string $sortBy = 'designation';

    #[Url(as: 'ordre', keep: false)]
    public string $sortDirection = 'asc';

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'par_page', keep: false)]
    public int $perPage = 10;

    public int $fournisseurId = 0;

    /** @var array<int> */
    public array $selectedProducts = [];

    /** @var array<int, string|float> */
    public array $prix = [];

    /** @var array<int, string|float> */
    public array $taxes = [];

    public bool $loading  = false;
    public bool $checkAll = false;

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(int $fournisseurId): void
    {
        $this->fournisseurId = $fournisseurId;
    }

    // ─── Sorting / Pagination ─────────────────────────────────────────────────

    public function sort(string $column): void
    {
        $allowed = ['product_code', 'designation'];

        if (!in_array($column, $allowed, true)) {
            return;
        }

        $this->sortDirection = ($this->sortBy === $column && $this->sortDirection === 'asc')
            ? 'desc'
            : 'asc';

        $this->sortBy = $column;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->syncCheckAll();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->syncCheckAll();
    }

    // ─── Selection ────────────────────────────────────────────────────────────

    public function toggleSelectAll(): void
    {
        $currentPageIds = $this->products->pluck('id')->toArray();

        $allCurrentSelected = !empty($currentPageIds)
            && count(array_intersect($currentPageIds, $this->selectedProducts)) === count($currentPageIds);

        if ($allCurrentSelected) {
            foreach ($currentPageIds as $id) {
                $this->selectedProducts = array_values(array_diff($this->selectedProducts, [$id]));
                unset($this->prix[$id], $this->taxes[$id]);
            }
            $this->checkAll = false;
        } else {
            foreach ($currentPageIds as $id) {
                if (!in_array($id, $this->selectedProducts, true)) {
                    $this->selectedProducts[] = $id;
                    $this->prix[$id]  = 0;
                    $this->taxes[$id] = 0;
                }
            }
            $this->checkAll = true;
        }
    }

    public function toggleProduct(int $id): void
    {
        if (in_array($id, $this->selectedProducts, true)) {
            $this->selectedProducts = array_values(array_diff($this->selectedProducts, [$id]));
            unset($this->prix[$id], $this->taxes[$id]);
        } else {
            $this->selectedProducts[] = $id;
            $this->prix[$id]  = 0;
            $this->taxes[$id] = 0;
        }

        $this->syncCheckAll();
    }

    public function clearSelection(): void
    {
        $this->selectedProducts = [];
        $this->prix             = [];
        $this->taxes            = [];
        $this->checkAll         = false;
    }

    private function syncCheckAll(): void
    {
        $currentPageIds = $this->products->pluck('id')->toArray();

        $this->checkAll = !empty($currentPageIds)
            && count(array_intersect($currentPageIds, $this->selectedProducts)) === count($currentPageIds);
    }

    public function isSelected(int $id): bool
    {
        return in_array($id, $this->selectedProducts, true);
    }

    // ─── Computed ─────────────────────────────────────────────────────────────

    #[Computed]
    public function alreadyLinkedIds(): array
    {
        return ProduitFournisseur::where('fournisseur_id', $this->fournisseurId)
            ->pluck('product_id')
            ->toArray();
    }

    #[Computed]
    public function products()
    {
        $query = Product::query()
            ->whereNotIn('id', $this->alreadyLinkedIds)
            ->with(['marque', 'categorie'])
            ->orderBy($this->sortBy, $this->sortDirection);

        if (!empty($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('designation',     'like', "%{$search}%")
                    ->orWhere('product_code',  'like', "%{$search}%")
                    ->orWhere('article',       'like', "%{$search}%")
                    ->orWhere('ref_fabri_n_1', 'like', "%{$search}%")
                    ->orWhere('EAN',           'like', "%{$search}%");
            });
        }

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function totalAvailable(): int
    {
        return Product::whereNotIn('id', $this->alreadyLinkedIds)->count();
    }

    #[Computed]
    public function isIndeterminate(): bool
    {
        $currentPageIds = $this->products->pluck('id')->toArray();
        $intersect      = count(array_intersect($currentPageIds, $this->selectedProducts));

        return $intersect > 0 && $intersect < count($currentPageIds);
    }

    // ─── Save / Cancel ────────────────────────────────────────────────────────

    public function save(): void
    {
        if (empty($this->selectedProducts)) {
            Flux::toast(
                heading: 'Aucun produit sélectionné',
                text: 'Veuillez sélectionner au moins un produit.',
                variant: 'warning'
            );
            return;
        }

        $this->loading = true;
        $savedCount    = 0;
        $skippedCount  = 0;
        $errors        = [];

        DB::beginTransaction();

        try {
            foreach ($this->selectedProducts as $productId) {
                $productId = (int) $productId;

                if (ProduitFournisseur::where('fournisseur_id', $this->fournisseurId)
                    ->where('product_id', $productId)
                    ->exists()) {
                    $skippedCount++;
                    continue;
                }

                $prix = $this->prix[$productId] ?? 0;
                $taxe = $this->taxes[$productId] ?? 0;

                if ($prix === '') $prix = 0;
                if ($taxe === '') $taxe = 0;

                if (!is_numeric($prix) || (float) $prix < 0) {
                    $errors[] = "Produit #{$productId} : le prix doit être un nombre positif.";
                    continue;
                }

                if (!is_numeric($taxe) || (float) $taxe < 0 || (float) $taxe > 100) {
                    $errors[] = "Produit #{$productId} : la taxe doit être entre 0 et 100.";
                    continue;
                }

                ProduitFournisseur::create([
                    'fournisseur_id'      => $this->fournisseurId,
                    'product_id'          => $productId,
                    'prix_fournisseur_ht' => (float) $prix,
                    'tax'                 => (float) $taxe,
                    'state'               => 1,
                ]);

                $savedCount++;
            }

            DB::commit();

            $this->dispatch('produit-fournisseur-created');
            $this->dispatch('fournisseur-products-updated');

            $this->resetForm();

            if ($savedCount > 0) {
                Flux::toast(
                    heading: 'Produits ajoutés',
                    text: "{$savedCount} produit(s) associé(s) avec succès."
                    . ($skippedCount > 0 ? " ({$skippedCount} déjà liés ignorés)" : ''),
                    variant: 'success'
                );
            }

            if (!empty($errors)) {
                Flux::toast(
                    heading: count($errors) . ' erreur(s)',
                    text: implode(' | ', $errors),
                    variant: 'warning'
                );
            }

            if ($savedCount === 0 && empty($errors)) {
                Flux::toast(
                    heading: 'Aucun produit ajouté',
                    text: 'Tous les produits sélectionnés étaient déjà associés.',
                    variant: 'info'
                );
            }

            $this->dispatch('close-modal', 'create-produit-fournisseur');

        } catch (\Throwable $e) {
            DB::rollBack();
            Flux::toast(
                heading: 'Erreur',
                text: "Une erreur est survenue : " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            $this->loading = false;
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->dispatch('close-modal', 'create-produit-fournisseur');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->reset(['selectedProducts', 'prix', 'taxes', 'search']);
        $this->checkAll = false;
        $this->resetPage();
    }
};
?>

<div>
    <flux:modal name="create-produit-fournisseur" class="max-w-7xl w-full" :dismissible="false">
        <div class="p-6 space-y-6">

            {{-- ── Header ─────────────────────────────────────────────────────── --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4">
                <flux:heading size="lg">
                    Ajouter des produits au fournisseur
                </flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    Sélectionnez les produits à associer à ce fournisseur.
                </flux:text>
                <div class="flex items-center gap-2 mt-3">
                    <flux:badge size="sm" color="info" class="gap-1">
                        <flux:icon name="shopping-bag" class="size-3" />
                        {{ $this->totalAvailable }} produit(s) disponible(s)
                    </flux:badge>
                    <flux:badge size="sm" color="zinc" class="gap-1">
                        <flux:icon name="check-circle" class="size-3" />
                        Non encore associés
                    </flux:badge>
                </div>
            </div>

            {{-- ── Toolbar ─────────────────────────────────────────────────────── --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher un produit..."
                        icon="magnifying-glass"
                        class="w-full sm:w-80"
                    />
                    <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                        <flux:select.option value="5">5</flux:select.option>
                        <flux:select.option value="10">10</flux:select.option>
                        <flux:select.option value="25">25</flux:select.option>
                        <flux:select.option value="50">50</flux:select.option>
                        <flux:select.option value="100">100</flux:select.option>
                    </flux:select>
                </div>

                @if(count($selectedProducts) > 0)
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="blue" class="gap-1">
                            <flux:icon name="check-circle" class="size-3" />
                            {{ count($selectedProducts) }} sélectionné(s)
                        </flux:badge>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            wire:click="clearSelection"
                            wire:loading.attr="disabled"
                            class="!px-2 !text-red-500"
                        >
                            Tout désélectionner
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- ── Table ───────────────────────────────────────────────────────── --}}
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden p-5">
                <flux:table :paginate="$this->products" class="w-full">
                    <flux:table.columns>

                        <flux:table.column class="w-12">
                            <flux:checkbox
                                wire:model="checkAll"
                                wire:click="toggleSelectAll"
                                :indeterminate="$this->isIndeterminate"
                            />
                        </flux:table.column>

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

                        <flux:table.column class="hidden md:table-cell">
                            Marque
                        </flux:table.column>

                        <flux:table.column class="hidden lg:table-cell">
                            Catégorie
                        </flux:table.column>

                        <flux:table.column>
                            Prix HT
                        </flux:table.column>

                        <flux:table.column>
                            Taxe (%)
                        </flux:table.column>

                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->products as $product)
                            <flux:table.row
                                :key="$product->id"
                                wire:key="product-{{ $product->id }}"
                                wire:click="toggleProduct({{ $product->id }})"
                                class="cursor-pointer select-none hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                            >
                                {{-- Check icon / Checkbox --}}
                                <flux:table.cell class="w-12">
                                    @if($this->isSelected($product->id))
                                        <span class="flex items-center justify-center text-blue-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5">
                                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    @else
                                        <flux:checkbox :checked="false" />
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" color="zinc" inset="top bottom">
                                        {{ $product->product_code }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <p class="font-medium text-sm">{{ $product->designation }}</p>
                                    <div class="flex flex-col gap-0.5 mt-1">
                                        @if($product->ref_fabri_n_1)
                                            <p class="text-xs text-zinc-400">Réf : {{ $product->ref_fabri_n_1 }}</p>
                                        @endif
                                        @if($product->EAN)
                                            <p class="text-xs text-zinc-400">EAN : {{ $product->EAN }}</p>
                                        @endif
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="hidden md:table-cell">
                                    <span class="text-sm">{{ $product->marque?->name ?? '—' }}</span>
                                </flux:table.cell>

                                <flux:table.cell class="hidden lg:table-cell">
                                    <span class="text-sm">{{ $product->categorie?->name ?? '—' }}</span>
                                </flux:table.cell>

                                {{-- Prix HT --}}
                                <flux:table.cell>
                                    <div wire:click.stop>
                                        <flux:input
                                            wire:model.blur="prix.{{ $product->id }}"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            size="sm"
                                            :disabled="!$this->isSelected($product->id)"
                                            class="w-28"
                                        />
                                        @error("prix.{$product->id}")
                                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </flux:table.cell>

                                {{-- Taxe % --}}
                                <flux:table.cell>
                                    <div wire:click.stop>
                                        <flux:input
                                            wire:model.blur="taxes.{{ $product->id }}"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            placeholder="0.00"
                                            size="sm"
                                            :disabled="!$this->isSelected($product->id)"
                                            class="w-24"
                                        />
                                        @error("taxes.{$product->id}")
                                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </flux:table.cell>

                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7">
                                    <div class="flex flex-col items-center justify-center py-12 text-center">
                                        <flux:icon name="shopping-bag" class="text-zinc-400 mb-3" style="width: 48px; height: 48px;" />
                                        <p class="text-zinc-500 font-medium text-sm">
                                            @if ($search)
                                                Aucun produit trouvé pour « {{ $search }} »
                                            @else
                                                Tous les produits sont déjà associés à ce fournisseur.
                                            @endif
                                        </p>
                                        @if (!$search && $this->totalAvailable === 0)
                                            <flux:text size="sm" class="text-zinc-400 mt-2">
                                                Il n'y a plus de produits disponibles à ajouter.
                                            </flux:text>
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            {{-- ── Actions ─────────────────────────────────────────────────────── --}}
            <div class="flex gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:spacer />
                <flux:button
                    variant="ghost"
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                >
                    Annuler
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    :disabled="count($selectedProducts) === 0 || $loading"
                    class="min-w-[120px]"
                >
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        Ajouter
                        @if(count($selectedProducts) > 0)
                            ({{ count($selectedProducts) }})
                        @endif
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        Ajout en cours…
                    </span>
                </flux:button>
            </div>

        </div>
    </flux:modal>

    @script
    <script>
        $wire.on('close-modal', (modalName) => {
            if (modalName === 'create-produit-fournisseur') {
                $flux.modal('create-produit-fournisseur').close();
            }
        });
    </script>
    @endscript
</div>
