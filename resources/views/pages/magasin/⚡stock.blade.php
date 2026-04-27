<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Magasin;
use App\Models\StockMagasin;

new class extends Component
{
    use WithPagination;

    public int $magasinId;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'etat', except: '')]
    public string $filterState = '';

    #[Url(as: 'par_page', except: 10)]
    public int $perPage = 10;

    public function mount(int $id): void
    {
        $this->magasinId = $id;
    }

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedPerPage(): void   { $this->resetPage(); }
    public function updatedFilterState(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'perPage']);
        $this->resetPage();
    }

    #[Computed]
    public function magasin(): Magasin
    {
        return Magasin::findOrFail($this->magasinId);
    }

    #[Computed]
    public function stockStats(): array
    {
        $base = StockMagasin::where('magasin_id', $this->magasinId);

        return [
            'total_references' => (clone $base)->distinct('product_id')->count('product_id'),
            'total_items'      => (clone $base)->sum('nb_item'),
            'active'           => (clone $base)->where('state', 1)->count(),
            'inactive'         => (clone $base)->where('state', 0)->count(),
        ];
    }

    #[Computed]
    public function stocks()
    {
        return StockMagasin::with(['product.marque', 'product.categorie'])
            ->where('magasin_id', $this->magasinId)
            ->when($this->search, fn($q) =>
            $q->whereHas('product', fn($p) =>
            $p->where('designation', 'like', "%{$this->search}%")
                ->orWhere('product_code', 'like', "%{$this->search}%")
                ->orWhere('article', 'like', "%{$this->search}%")
                ->orWhere('EAN', 'like', "%{$this->search}%")
            )
                ->orWhere('gen_code', 'like', "%{$this->search}%")
            )
            ->when($this->filterState !== '', fn($q) =>
            $q->where('state', $this->filterState)
            )
            ->latest('deposite_date')
            ->paginate($this->perPage);
    }
};
?>

<div class="max-w-7xl mx-auto">

    {{-- Breadcrumbs --}}
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item :href="route('magasin.index')">Dépôt</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $this->magasin->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
        <div class="flex items-start gap-3">
            <flux:button
                :href="route('magasin.index')"
                variant="ghost"
                icon="arrow-left"
                size="sm"
                class="mt-1"
            />
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:heading size="xl" level="1">{{ $this->magasin->name }}</flux:heading>
                    @if ($this->magasin->type)
                        <flux:badge color="purple" size="sm">{{ $this->magasin->type }}</flux:badge>
                    @endif
                    <flux:badge
                        :color="$this->magasin->state == 1 ? 'green' : 'zinc'"
                        size="sm"
                    >
                        {{ $this->magasin->state == 1 ? 'Actif' : 'Inactif' }}
                    </flux:badge>
                </div>

                {{-- Infos contact --}}
                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-zinc-500">
                    @if ($this->magasin->email)
                        <span class="flex items-center gap-1">
                            <flux:icon name="envelope" class="w-3.5 h-3.5" />
                            {{ $this->magasin->email }}
                        </span>
                    @endif
                    @if ($this->magasin->telephone)
                        <span class="flex items-center gap-1">
                            <flux:icon name="phone" class="w-3.5 h-3.5" />
                            {{ $this->magasin->telephone }}
                        </span>
                    @endif
                    @if ($this->magasin->adress)
                        <span class="flex items-center gap-1">
                            <flux:icon name="map-pin" class="w-3.5 h-3.5" />
                            {{ $this->magasin->adress }}
                        </span>
                    @endif
                    @if ($this->magasin->store_url)
                        <a
                            href="{{ $this->magasin->store_url }}"
                            target="_blank"
                            class="flex items-center gap-1 text-blue-500 hover:underline"
                        >
                            <flux:icon name="link" class="w-3.5 h-3.5" />
                            {{ $this->magasin->store_url }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <flux:modal.trigger name="edit-magasin-{{ $this->magasin->id }}">
                <flux:button variant="ghost" icon="pencil" size="sm">
                    Modifier
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Références</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stockStats['total_references'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Total articles</p>
            <p class="text-3xl font-bold mt-1">{{ number_format($this->stockStats['total_items'], 0, ',', ' ') }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Entrées actives</p>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stockStats['active'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Entrées inactives</p>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stockStats['inactive'] }}</p>
        </flux:card>
    </div>

    {{-- Stock Table --}}
    <flux:card class="p-5">

        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg" level="2">Stock du dépôt</flux:heading>
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher un produit, code, EAN..."
                    icon="magnifying-glass"
                    class="w-full sm:w-80"
                />

                <flux:radio.group wire:model.live="filterState" variant="segmented">
                    <flux:radio label="Tous"    value=""  />
                    <flux:radio label="Actif"   value="1" />
                    <flux:radio label="Inactif" value="0" />
                </flux:radio.group>

                @if ($search || $filterState !== '')
                    <flux:button
                        wire:click="resetFilters"
                        variant="ghost"
                        icon="x-mark"
                        size="sm"
                        class="text-red-500"
                    />
                @endif
            </div>

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>

        {{-- Table --}}
        <flux:table :paginate="$this->stocks" variant="bordered">
            <flux:table.columns>
                <flux:table.column>Produit</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Code / EAN</flux:table.column>
                <flux:table.column class="hidden md:table-cell">Marque / Catégorie</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">Gen Code</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">Date dépôt</flux:table.column>
                <flux:table.column class="text-right">Qté</flux:table.column>
                <flux:table.column class="text-center">État</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->stocks as $stock)
                    <flux:table.row :key="$stock->id" wire:key="stock-{{ $stock->id }}">

                        {{-- Produit --}}
                        <flux:table.cell>
                            <p class="font-medium text-sm">
                                {{ $stock->product?->designation ?? '—' }}
                            </p>
                            @if ($stock->product?->designation_variant)
                                <p class="text-xs text-zinc-400">{{ $stock->product->designation_variant }}</p>
                            @endif
                            {{-- Mobile: extra info --}}
                            <div class="sm:hidden text-xs text-zinc-400 mt-0.5 space-y-0.5">
                                @if ($stock->product?->product_code)
                                    <p>{{ $stock->product->product_code }}</p>
                                @endif
                                @if ($stock->product?->EAN)
                                    <p>EAN: {{ $stock->product->EAN }}</p>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Code / EAN --}}
                        <flux:table.cell class="hidden sm:table-cell">
                            <div class="space-y-0.5">
                                @if ($stock->product?->product_code)
                                    <p class="text-xs font-mono text-zinc-600 dark:text-zinc-300">
                                        {{ $stock->product->product_code }}
                                    </p>
                                @endif
                                @if ($stock->product?->EAN)
                                    <p class="text-xs text-zinc-400">{{ $stock->product->EAN }}</p>
                                @endif
                                @if (!$stock->product?->product_code && !$stock->product?->EAN)
                                    <span class="text-zinc-400 text-sm">—</span>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Marque / Catégorie --}}
                        <flux:table.cell class="hidden md:table-cell">
                            <div class="space-y-1">
                                @if ($stock->product?->marque)
                                    <flux:badge size="sm" color="blue" inset="top bottom">
                                        {{ $stock->product->marque->name }}
                                    </flux:badge>
                                @endif
                                @if ($stock->product?->categorie)
                                    <flux:badge size="sm" color="amber" inset="top bottom">
                                        {{ $stock->product->categorie->name }}
                                    </flux:badge>
                                @endif
                                @if (!$stock->product?->marque && !$stock->product?->categorie)
                                    <span class="text-zinc-400 text-sm">—</span>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Gen Code --}}
                        <flux:table.cell class="hidden lg:table-cell">
                            <p class="text-xs font-mono text-zinc-500 truncate max-w-[160px]">
                                {{ $stock->gen_code ?? '—' }}
                            </p>
                        </flux:table.cell>

                        {{-- Date dépôt --}}
                        <flux:table.cell class="hidden lg:table-cell">
                            @if ($stock->deposite_date)
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ \Carbon\Carbon::parse($stock->deposite_date)->format('d/m/Y') }}
                                </p>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Quantité --}}
                        <flux:table.cell class="text-right">
                            <span class="font-semibold text-sm {{ $stock->nb_item <= 0 ? 'text-red-500' : '' }}">
                                {{ number_format($stock->nb_item, 0, ',', ' ') }}
                            </span>
                        </flux:table.cell>

                        {{-- État --}}
                        <flux:table.cell class="text-center">
                            <flux:badge
                                :color="$stock->state == 1 ? 'green' : 'zinc'"
                                size="sm"
                            >
                                {{ $stock->state == 1 ? 'Actif' : 'Inactif' }}
                            </flux:badge>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon name="archive-box" class="text-zinc-400 mb-3" style="width:40px;height:40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterState !== '')
                                        Aucun stock trouvé pour ces filtres
                                    @else
                                        Aucun stock enregistré pour ce dépôt
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

    </flux:card>

</div>
