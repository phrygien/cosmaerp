<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Marque;

new class extends Component
{
    use WithPagination;

    public string $marqueCode;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'par_page', except: 10)]
    public int $perPage = 10;

    public function mount(string $marque): void
    {
        $this->marqueCode = $marque;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function marque()
    {
        return Marque::withCount([
            'products',
            'products as products_active_count' => fn($q) => $q->where('state', 1),
            'products as products_inactive_count' => fn($q) => $q->where('state', 0),
        ])
            ->findOrFail($this->marqueCode);
    }

    #[Computed]
    public function products()
    {
        return $this->marque->products()
            ->with(['categorie', 'type', 'ligne'])
            ->when($this->search, fn($q) => $q->where(fn($qq) =>
            $qq->where('designation', 'like', "%{$this->search}%")
                ->orWhere('product_code', 'like', "%{$this->search}%")
                ->orWhere('article', 'like', "%{$this->search}%")
            ))
            ->orderBy('designation')
            ->paginate($this->perPage);
    }
};
?>

<div class="max-w-5xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('catalogue.marques') }}" wire:navigate>Marque</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $this->marque->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <flux:heading size="xl" level="1">{{ $this->marque->name }}</flux:heading>
            <flux:badge size="sm" :color="$this->marque->isActive() ? 'green' : 'zinc'">
                {{ $this->marque->isActive() ? 'Actif' : 'Inactif' }}
            </flux:badge>
        </div>

        <flux:button href="{{ route('catalogue.marques') }}" wire:navigate variant="danger" color="rose">
            Retour
        </flux:button>
        {{-- Note: adapte 'catalogue.marques' si le nom réel de la route liste diffère --}}
    </div>

    <!-- Infos marque -->
    <flux:card class="p-5 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-zinc-500">Code</p>
                <flux:badge size="sm" color="zinc" class="mt-1">{{ $this->marque->code }}</flux:badge>
            </div>
            <div>
                <p class="text-sm text-zinc-500">Nom</p>
                <p class="font-medium text-sm mt-1">{{ $this->marque->name }}</p>
            </div>
        </div>
    </flux:card>

    <!-- Stat Cards produits -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Total Produits</p>
                <i class="hgi-stroke hgi-package text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1">{{ $this->marque->products_count }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Produits Actifs</p>
                <i class="hgi-stroke hgi-checkmark-circle-01 text-2xl text-green-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->marque->products_active_count }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Produits Inactifs</p>
                <i class="hgi-stroke hgi-cancel-circle text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->marque->products_inactive_count }}</p>
        </flux:card>
    </div>

    <!-- Produits de la marque -->
    <flux:card class="p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <flux:heading size="lg">Produits</flux:heading>

            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher un produit..."
                    icon="magnifying-glass"
                    class="w-full sm:w-72"
                />

                <flux:select wire:model.live="perPage" class="w-20">
                    <flux:select.option value="5">5</flux:select.option>
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table :paginate="$this->products" variant="bordered">
            <flux:table.columns>
                <flux:table.column>Code</flux:table.column>
                <flux:table.column>Désignation</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Catégorie</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Type</flux:table.column>
                <flux:table.column class="text-center">État</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->products as $product)
                    <flux:table.row :key="$product->id" wire:key="product-{{ $product->id }}">
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $product->product_code }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $product->designation }}</p>
                            @if($product->designation_variant)
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $product->designation_variant }}</p>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell">
                            {{ $product->categorie?->name ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell">
                            {{ $product->type?->name ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            <flux:badge size="sm" :color="$product->state == 1 ? 'green' : 'zinc'">
                                {{ $product->state == 1 ? 'Actif' : 'Inactif' }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <i class="hgi-stroke hgi-package text-5xl text-zinc-400 mb-3"></i>
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if($search)
                                        Aucun produit trouvé pour cette recherche
                                    @else
                                        Aucun produit pour cette marque
                                    @endif
                                </p>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
