<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Facture;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy        = 'created_at';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'desc';

    #[Url(as: 'q', except: '')]
    public string $search        = '';

    #[Url(as: 'etat', except: '')]
    public string $filterState   = '';

    #[Url(as: 'type', except: '')]
    public string $filterType    = '';

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    public bool $showFilters = false;

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
    public function updatedFilterType(): void  { $this->resetPage(); }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'filterType', 'perPage']);
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    #[On('facture-created')]
    #[On('facture-updated')]
    #[On('facture-deleted')]
    public function refresh(): void
    {
        unset($this->factures);
        unset($this->stats);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->dispatch('edit-facture', id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-facture', id: $id);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total'    => Facture::count(),
            'montant'  => Facture::sum('montant'),
            'en_cours' => Facture::where('state', 0)->count(),
            'valides'  => Facture::where('state', 1)->count(),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->filterState, $this->filterType])
            ->filter(fn($v) => $v !== '')
            ->count();
    }

    #[Computed]
    public function factures()
    {
        return Facture::query()
            ->with(['forfaisseur', 'commande'])
            ->withCount('detailsFacture')
            ->when($this->search, fn($q) => $q
                ->where('numero', 'like', "%{$this->search}%")
                ->orWhere('libelle', 'like', "%{$this->search}%")
                ->orWhereHas('forfaisseur', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            )
            ->when($this->filterState !== '', fn($q) => $q->where('state', $this->filterState))
            ->when($this->filterType  !== '', fn($q) => $q->where('type', $this->filterType))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Factures</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Liste</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Factures') }}</flux:heading>

        <flux:modal.trigger name="create-facture">
            <flux:button variant="primary" class="w-full sm:w-auto" href="{{ route('facturation.create') }}" wire:navigate>
                Ajouter une facture
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Total Factures</p>
                <i class="hgi-stroke hgi-invoice-01 text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Montant Total</p>
                <i class="hgi-stroke hgi-money-bag-01 text-2xl text-blue-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-blue-500">
                {{ number_format($this->stats['montant'], 2, ',', ' ') }} €
            </p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Validées</p>
                <i class="hgi-stroke hgi-checkmark-circle-01 text-2xl text-green-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['valides'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">En cours</p>
                <i class="hgi-stroke hgi-clock-01 text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['en_cours'] }}</p>
        </flux:card>
    </div>

    <flux:card class="p-5">

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher une facture..."
                    icon="magnifying-glass"
                    class="w-full sm:w-72"
                />

                <div class="relative">
                    <flux:button
                        wire:click="toggleFilters"
                        :variant="$showFilters ? 'primary' : 'ghost'"
                        size="sm"
                    >
                        <i class="hgi-stroke hgi-filter-01"></i>
                        Filtres
                    </flux:button>
                    @if($this->activeFiltersCount > 0)
                        <span class="absolute -top-1.5 -right-1.5 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full">
                            {{ $this->activeFiltersCount }}
                        </span>
                    @endif
                </div>
            </div>

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>

        @if($showFilters)
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 mb-4 bg-zinc-50 dark:bg-zinc-800/50">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Filtres</p>
                    @if($this->activeFiltersCount > 0)
                        <flux:button wire:click="resetFilters" variant="ghost" size="xs" class="text-red-500 hover:text-red-600">
                            Réinitialiser
                        </flux:button>
                    @endif
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
                    <div>
                        <p class="text-xs text-zinc-500 mb-1">État</p>
                        <flux:radio.group wire:model.live="filterState" variant="segmented">
                            <flux:radio label="Tous"      value=""  />
                            <flux:radio label="Validée"   value="1" />
                            <flux:radio label="En cours"  value="0" />
                        </flux:radio.group>
                    </div>

                    <div>
                        <p class="text-xs text-zinc-500 mb-1">Type</p>
                        <flux:radio.group wire:model.live="filterType" variant="segmented">
                            <flux:radio label="Tous"    value=""  />
                            <flux:radio label="Achat"   value="achat" />
                            <flux:radio label="Avoir"   value="avoir" />
                        </flux:radio.group>
                    </div>
                </div>
            </div>
        @endif

        <flux:table :paginate="$this->factures" variant="bordered">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'numero'" :direction="$sortDirection" wire:click="sort('numero')">N° Facture</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'libelle'" :direction="$sortDirection" wire:click="sort('libelle')">Libellé</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Fournisseur</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortBy === 'date_commande'" :direction="$sortDirection" wire:click="sort('date_commande')">Date</flux:table.column>
                <flux:table.column class="hidden md:table-cell" sortable :sorted="$sortBy === 'montant'" :direction="$sortDirection" wire:click="sort('montant')">Montant</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Lignes</flux:table.column>
                <flux:table.column class="text-center">Type</flux:table.column>
                <flux:table.column class="text-center">État</flux:table.column>
                <flux:table.column class="text-right">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->factures as $facture)
                    <flux:table.row :key="$facture->id" wire:key="facture-{{ $facture->id }}">

                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $facture->numero ?? '—' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $facture->libelle ?? '—' }}</p>
                            {{-- Mobile: infos condensées --}}
                            <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                                {{ $facture->forfaisseur?->name ?? '—' }}
                            </p>
                            <p class="text-xs text-zinc-400 sm:hidden">
                                {{ $facture->date_commande ? \Carbon\Carbon::parse($facture->date_commande)->format('d/m/Y') : '—' }}
                            </p>
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell">
                            <p class="text-sm">{{ $facture->forfaisseur?->name ?? '—' }}</p>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            <p class="text-sm text-zinc-500">
                                {{ $facture->date_commande ? \Carbon\Carbon::parse($facture->date_commande)->format('d/m/Y') : '—' }}
                            </p>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            <p class="text-sm font-medium">
                                {{ number_format($facture->montant ?? 0, 2, ',', ' ') }} €
                            </p>
                            @if($facture->remise)
                                <p class="text-xs text-zinc-400">Remise : {{ $facture->remise }} %</p>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell">
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                {{ $facture->details_facture_count }} ligne{{ $facture->details_facture_count > 1 ? 's' : '' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            <flux:badge
                                size="sm"
                                :color="$facture->type === 'avoir' ? 'orange' : 'indigo'"
                                inset="top bottom"
                            >
                                {{ ucfirst($facture->type ?? '—') }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            @if($facture->state == 1)
                                <flux:badge size="sm" color="green" inset="top bottom">Validée</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc" inset="top bottom">En cours</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    inset="top bottom"
                                    :href="route('facturation.edit', $facture->id)"
                                    title="Modifier"
                                >
                                    <i class="hgi-stroke hgi-pencil-edit-01 text-indigo-400"></i>
                                </flux:button>

                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    inset="top bottom"
                                    wire:click="confirmDelete({{ $facture->id }})"
                                    title="Supprimer"
                                >
                                    <i class="hgi-stroke hgi-delete-02 text-red-400"></i>
                                </flux:button>
                            </div>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <i class="hgi-stroke hgi-invoice-01 text-5xl text-zinc-400 mb-3"></i>
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterState !== '' || $filterType !== '')
                                        Aucune facture trouvée pour ces filtres
                                    @else
                                        Aucune facture enregistrée
                                    @endif
                                </p>
                                @if ($search || $filterState !== '' || $filterType !== '')
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

    <livewire:pages::facturation.delete />
</div>
