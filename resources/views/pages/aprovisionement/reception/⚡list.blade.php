<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\ReceptionCommande;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy = 'created_at';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'desc';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'etat', except: '')]
    public string $filterState = '';

    #[Url(as: 'par_page', except: 15)]
    public int $perPage = 15;

    public bool $showFilters = false;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedPerPage(): void   { $this->resetPage(); }
    public function updatedFilterState(): void { $this->resetPage(); }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'perPage']);
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total'    => ReceptionCommande::count(),
            'recu'     => ReceptionCommande::where('state', 'received')->count(),
            'partiel'  => ReceptionCommande::where('state', 'partial')->count(),
            'attente'  => ReceptionCommande::where('state', 'pending')->count(),
        ];
    }

    #[Computed]
    public function receptions()
    {
        return ReceptionCommande::query()
            ->with([
                'bon_commande.magasinLivraison',
                'commande.fournisseur',
                'detail_commande.product',
            ])
            ->when($this->search, fn($q) =>
            $q->whereHas('commande.fournisseur', fn($q) =>
            $q->where('nom', 'like', "%{$this->search}%")
            )
                ->orWhereHas('detail_commande.product', fn($q) =>
                $q->where('nom', 'like', "%{$this->search}%")
                )
                ->orWhereHas('bon_commande', fn($q) =>
                $q->where('code_fournisseur', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterState !== '', fn($q) => $q->where('state', $this->filterState))
            ->tap(fn($q) => $this->sortBy ? $q->orderBy($this->sortBy, $this->sortDirection) : $q)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->filterState])
            ->filter(fn($v) => $v !== '')
            ->count();
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">{{ __('Réception des commandes') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Liste') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Réception des commandes') }}</flux:heading>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">{{ __('Total') }}</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">{{ __('Reçus') }}</p>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['recu'] }}</p>
        </flux:card>
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">{{ __('Partiels') }}</p>
            <p class="text-3xl font-bold mt-1 text-yellow-500">{{ $this->stats['partiel'] }}</p>
        </flux:card>
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">{{ __('En attente') }}</p>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['attente'] }}</p>
        </flux:card>
    </div>

    <flux:card class="p-5 mt-5">

        {{-- En-tête : recherche | toggle filtres | per page --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('Fournisseur, produit, code fournisseur...') }}"
                    icon="magnifying-glass"
                    class="w-full sm:w-72"
                />

                <div class="relative">
                    <flux:button
                        wire:click="toggleFilters"
                        :variant="$showFilters ? 'primary' : 'ghost'"
                        icon="funnel"
                        size="sm"
                    >
                        {{ __('Filtres') }}
                    </flux:button>
                    @if ($this->activeFiltersCount > 0)
                        <span class="absolute -top-1.5 -right-1.5 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold leading-none text-white bg-red-500 rounded-full">
                            {{ $this->activeFiltersCount }}
                        </span>
                    @endif
                </div>
            </div>

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="15">15</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>

        {{-- Panneau de filtres --}}
        @if ($showFilters)
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 mb-4 bg-zinc-50 dark:bg-zinc-800/50">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Filtres') }}</p>
                    @if ($this->activeFiltersCount > 0)
                        <flux:button wire:click="resetFilters" variant="ghost" size="xs" class="text-red-500 hover:text-red-600">
                            {{ __('Réinitialiser') }}
                        </flux:button>
                    @endif
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
                    <flux:radio.group wire:model.live="filterState" variant="segmented">
                        <flux:radio label="{{ __('Tous') }}"        value=""         />
                        <flux:radio label="{{ __('En attente') }}"  value="pending"  />
                        <flux:radio label="{{ __('Reçu') }}"        value="received" />
                        <flux:radio label="{{ __('Partiel') }}"     value="partial"  />
                        <flux:radio label="{{ __('Rejeté') }}"      value="rejected" />
                    </flux:radio.group>
                </div>
            </div>
        @endif

        {{-- Table --}}
        <flux:table :paginate="$this->receptions" variant="bordered">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                >
                    {{ __('Date réception') }}
                </flux:table.column>

                <flux:table.column>{{ __('Fournisseur') }}</flux:table.column>
                <flux:table.column>{{ __('Bon de commande') }}</flux:table.column>
                <flux:table.column>{{ __('Produit') }}</flux:table.column>

                <flux:table.column class="hidden md:table-cell">
                    {{ __('Livraison prévue') }}
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'recu'"
                    :direction="$sortDirection"
                    wire:click="sort('recu')"
                >
                    {{ __('Reçu / Commandé') }}
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'invendable'"
                    :direction="$sortDirection"
                    wire:click="sort('invendable')"
                    class="hidden sm:table-cell"
                >
                    {{ __('Invendable') }}
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'state'"
                    :direction="$sortDirection"
                    wire:click="sort('state')"
                >
                    {{ __('État') }}
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->receptions as $reception)
                    <flux:table.row :key="$reception->id" wire:key="reception-{{ $reception->id }}">

                        {{-- Date réception --}}
                        <flux:table.cell class="text-sm whitespace-nowrap">
                            <div>{{ $reception->created_at->format('d/m/Y') }}</div>
                            <div class="text-zinc-400 text-xs">{{ $reception->created_at->format('H:i') }}</div>
                        </flux:table.cell>

                        {{-- Fournisseur --}}
                        <flux:table.cell>
                            @if ($reception->commande?->fournisseur)
                                <p class="font-medium text-sm">{{ $reception->commande->fournisseur->nom }}</p>
                                @if ($reception->bon_commande?->code_fournisseur)
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ $reception->bon_commande->code_fournisseur }}</p>
                                @endif
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Bon de commande --}}
                        <flux:table.cell class="text-sm">
                            @if ($reception->bon_commande)
                                <div>{{ $reception->bon_commande->numero_compte ?? '#' . $reception->bon_commande->id }}</div>
                                @if ($reception->bon_commande->magasinLivraison)
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ $reception->bon_commande->magasinLivraison->nom }}</p>
                                @endif
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Produit --}}
                        <flux:table.cell>
                            @if ($reception->detail_commande?->product)
                                <p class="font-medium text-sm">{{ $reception->detail_commande->product->nom }}</p>
                                <p class="text-xs text-zinc-400 mt-0.5">
                                    {{ number_format($reception->detail_commande->pu_achat_net, 2) }} €
                                    @if ($reception->detail_commande->taux_remise > 0)
                                        · <span class="text-green-500">-{{ $reception->detail_commande->taux_remise }}%</span>
                                    @endif
                                </p>
                                {{-- Infos mobiles --}}
                                <div class="text-xs text-zinc-400 mt-1 md:hidden">
                                    @if ($reception->bon_commande?->date_livraison_prevue)
                                        <div>{{ __('Livraison') }}: {{ \Carbon\Carbon::parse($reception->bon_commande->date_livraison_prevue)->format('d/m/Y') }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Livraison prévue --}}
                        <flux:table.cell class="hidden md:table-cell text-sm whitespace-nowrap">
                            @if ($reception->bon_commande?->date_livraison_prevue)
                                @php
                                    $dateLivraison = \Carbon\Carbon::parse($reception->bon_commande->date_livraison_prevue);
                                    $isLate = $dateLivraison->isPast() && $reception->state !== 'received';
                                @endphp
                                <span @class(['text-red-500' => $isLate])>
                                    {{ $dateLivraison->format('d/m/Y') }}
                                </span>
                                @if ($isLate)
                                    <flux:badge size="sm" color="red" inset="top bottom" class="ml-1">{{ __('Retard') }}</flux:badge>
                                @endif
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        {{-- Reçu / Commandé --}}
                        <flux:table.cell variant="strong">
                            @php
                                $quantite = $reception->detail_commande?->quantite ?? 0;
                                $recu     = $reception->recu ?? 0;
                                $pct      = $quantite > 0 ? round(($recu / $quantite) * 100) : 0;
                            @endphp
                            <div>{{ $recu }} / {{ $quantite }}</div>
                            <div class="text-xs text-zinc-400">{{ $pct }}%</div>
                        </flux:table.cell>

                        {{-- Invendable --}}
                        <flux:table.cell class="hidden sm:table-cell">
                            @if ($reception->invendable > 0)
                                <flux:badge size="sm" color="red" inset="top bottom">{{ $reception->invendable }}</flux:badge>
                            @else
                                <span class="text-zinc-400">0</span>
                            @endif
                        </flux:table.cell>

                        {{-- État --}}
                        <flux:table.cell>
                            @php
                                $badge = match($reception->state) {
                                    'received' => ['color' => 'green',  'label' => __('Reçu')],
                                    'partial'  => ['color' => 'yellow', 'label' => __('Partiel')],
                                    'rejected' => ['color' => 'red',    'label' => __('Rejeté')],
                                    default    => ['color' => 'zinc',   'label' => __('En attente')],
                                };
                            @endphp
                            <flux:badge size="sm" :color="$badge['color']" inset="top bottom">
                                {{ $badge['label'] }}
                            </flux:badge>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon name="inbox" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterState !== '')
                                        {{ __('Aucune réception trouvée pour ces filtres') }}
                                    @else
                                        {{ __('Aucune réception enregistrée') }}
                                    @endif
                                </p>
                                @if ($search || $filterState !== '')
                                    <flux:button variant="ghost" size="sm" wire:click="resetFilters" class="mt-3">
                                        {{ __('Réinitialiser les filtres') }}
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
