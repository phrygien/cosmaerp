<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\BonCommande;
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

    public array $expandedIds = [];

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

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function toggleExpand(int $id): void
    {
        if (in_array($id, $this->expandedIds)) {
            $this->expandedIds = array_values(array_diff($this->expandedIds, [$id]));
        } else {
            $this->expandedIds[] = $id;
        }
    }

    public function expandAll(): void
    {
        $this->expandedIds = $this->bonCommandes->pluck('id')->toArray();
    }

    public function collapseAll(): void
    {
        $this->expandedIds = [];
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'perPage']);
        $this->expandedIds = [];
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
            'total'   => ReceptionCommande::count(),
            'recu'    => ReceptionCommande::where('state', 'received')->count(),
            'partiel' => ReceptionCommande::where('state', 'partial')->count(),
            'attente' => ReceptionCommande::where('state', 'pending')->count(),
        ];
    }

    #[Computed]
    public function bonCommandes()
    {
        return BonCommande::query()
            ->with([
                'commande.fournisseur',
                'commande.details.product',      // tous les produits de la commande
                'receptions.detail_commande',    // pour filtrer par detail_commande_id
                'magasinLivraison',
            ])
            ->withCount('receptions')
            ->withSum('receptions', 'recu')
            ->withSum('receptions', 'invendable')
            ->when($this->search, fn($q) =>
            $q->whereHas('commande.fournisseur', fn($q) =>
            $q->where('nom', 'like', "%{$this->search}%")
            )
                ->orWhere('code_fournisseur', 'like', "%{$this->search}%")
                ->orWhere('numero_compte', 'like', "%{$this->search}%")
            )
            ->when($this->filterState !== '', fn($q) =>
            $q->whereHas('receptions', fn($q) =>
            $q->where('state', $this->filterState)
            )
            )
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
            <p class="text-sm text-zinc-500">{{ __('Total réceptions') }}</p>
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

        {{-- En-tête --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('Fournisseur, n° compte, code fournisseur...') }}"
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

            <div class="flex items-center gap-2">
                @if (count($this->expandedIds) > 0)
                    <flux:button wire:click="collapseAll" variant="ghost" size="sm" icon="chevron-up">
                        {{ __('Tout réduire') }}
                    </flux:button>
                @else
                    <flux:button wire:click="expandAll" variant="ghost" size="sm" icon="chevron-down">
                        {{ __('Tout déplier') }}
                    </flux:button>
                @endif

                <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="15">15</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            </div>
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
                <flux:radio.group wire:model.live="filterState" variant="segmented">
                    <flux:radio label="{{ __('Tous') }}"       value=""         />
                    <flux:radio label="{{ __('En attente') }}" value="pending"  />
                    <flux:radio label="{{ __('Reçu') }}"       value="received" />
                    <flux:radio label="{{ __('Partiel') }}"    value="partial"  />
                    <flux:radio label="{{ __('Rejeté') }}"     value="rejected" />
                </flux:radio.group>
            </div>
        @endif

        {{-- Table --}}
        <flux:table :paginate="$this->bonCommandes" variant="bordered">
            <flux:table.columns>
                <flux:table.column class="w-8"></flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                >{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Bon de commande') }}</flux:table.column>
                <flux:table.column>{{ __('Fournisseur') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Livraison prévue') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell text-center">{{ __('Réceptions') }}</flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'montant_commande_net'"
                    :direction="$sortDirection"
                    wire:click="sort('montant_commande_net')"
                    class="hidden md:table-cell"
                >{{ __('Montant net') }}</flux:table.column>
                <flux:table.column class="text-center">{{ __('Invendable') }}</flux:table.column>
                <flux:table.column>{{ __('Magasin livraison') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->bonCommandes as $bon)
                    @php $isExpanded = in_array($bon->id, $this->expandedIds); @endphp

                    {{-- ── Ligne Bon de commande ────────────────────────────── --}}
                    <flux:table.row
                        :key="$bon->id"
                        wire:key="bon-{{ $bon->id }}"
                        wire:click="toggleExpand({{ $bon->id }})"
                        class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors {{ $isExpanded ? 'bg-zinc-50 dark:bg-zinc-800/40 font-medium' : '' }}"
                    >
                        <flux:table.cell>
                            <flux:icon
                                name="{{ $isExpanded ? 'chevron-down' : 'chevron-right' }}"
                                class="size-4 text-zinc-400"
                            />
                        </flux:table.cell>

                        <flux:table.cell class="text-sm whitespace-nowrap">
                            <div>{{ $bon->created_at->format('d/m/Y') }}</div>
                            <div class="text-zinc-400 text-xs">{{ $bon->created_at->format('H:i') }}</div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <p class="font-semibold text-sm">{{ $bon->numero_compte ?? '#' . $bon->id }}</p>
                            @if ($bon->code_fournisseur)
                                <p class="text-xs text-zinc-400 mt-0.5">{{ $bon->code_fournisseur }}</p>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($bon->commande?->fournisseur)
                                <p class="font-medium text-sm">{{ $bon->commande->fournisseur->nom }}</p>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell text-sm whitespace-nowrap">
                            @if ($bon->date_livraison_prevue)
                                @php
                                    $dateLivraison = \Carbon\Carbon::parse($bon->date_livraison_prevue);
                                    $isLate = $dateLivraison->isPast();
                                @endphp
                                <span @class(['text-red-500 font-medium' => $isLate])>
                                    {{ $dateLivraison->format('d/m/Y') }}
                                </span>
                                @if ($isLate)
                                    <flux:badge size="sm" color="red" inset="top bottom" class="ml-1">{{ __('Retard') }}</flux:badge>
                                @endif
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell text-center">
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $bon->receptions_count }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell" variant="strong">
                            {{ $bon->montant_commande_net ? number_format($bon->montant_commande_net, 2) . ' €' : '—' }}
                        </flux:table.cell>

                        <flux:table.cell class="text-center">
                            @if ($bon->receptions_sum_invendable > 0)
                                <flux:badge size="sm" color="red" inset="top bottom">{{ $bon->receptions_sum_invendable }}</flux:badge>
                            @else
                                <span class="text-zinc-400">0</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-sm">
                            {{ $bon->magasinLivraison?->nom ?? '—' }}
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- ── Lignes enfants groupées par produit (DetailCommande) ── --}}
                    @if ($isExpanded)
                        @forelse ($bon->commande?->details ?? [] as $detail)
                            @php
                                // Réceptions liées à ce detail_commande
                                $receptionsDetail = $bon->receptions
                                    ->where('detail_commande_id', $detail->id);

                                $totalRecu      = $receptionsDetail->sum('recu');
                                $totalInvendable = $receptionsDetail->sum('invendable');
                                $pct = $detail->quantite > 0
                                    ? round(($totalRecu / $detail->quantite) * 100)
                                    : 0;
                            @endphp

                            {{-- Ligne produit (niveau 2) --}}
                            <flux:table.row
                                wire:key="detail-{{ $bon->id }}-{{ $detail->id }}"
                                class="bg-zinc-50/80 dark:bg-zinc-800/30"
                            >
                                {{-- Indentation niveau 2 --}}
                                <flux:table.cell>
                                    <div class="w-3 border-b border-l border-zinc-300 dark:border-zinc-600 h-4 ml-3 rounded-bl"></div>
                                </flux:table.cell>

                                {{-- Produit --}}
                                <flux:table.cell colspan="3">
                                    <div class="flex items-center gap-2 pl-1">
                                        <flux:icon name="cube" class="size-4 text-zinc-400 shrink-0" />
                                        <div>
                                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                                {{ $detail->product?->nom ?? '—' }}
                                            </p>
                                            <p class="text-xs text-zinc-400 mt-0.5">
                                                PU net : {{ number_format($detail->pu_achat_net, 2) }} €
                                                @if ($detail->taux_remise > 0)
                                                    · <span class="text-green-500">-{{ $detail->taux_remise }}%</span>
                                                @endif
                                                · TVA {{ $detail->tax }}%
                                            </p>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                {{-- Livraison prévue : vide --}}
                                <flux:table.cell class="hidden md:table-cell"></flux:table.cell>

                                {{-- Progression globale du produit --}}
                                <flux:table.cell class="hidden sm:table-cell text-center">
                                    <div class="text-xs font-medium">{{ $totalRecu }} / {{ $detail->quantite }}</div>
                                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 mt-1">
                                        <div
                                            class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-green-500' : ($pct > 0 ? 'bg-yellow-400' : 'bg-zinc-300') }}"
                                            style="width: {{ min($pct, 100) }}%"
                                        ></div>
                                    </div>
                                    <div class="text-xs text-zinc-400 mt-0.5">{{ $pct }}%</div>
                                </flux:table.cell>

                                {{-- Montant ligne --}}
                                <flux:table.cell class="hidden md:table-cell" variant="strong">
                                    {{ number_format($detail->pu_achat_net * $detail->quantite, 2) }} €
                                </flux:table.cell>

                                {{-- Invendable total produit --}}
                                <flux:table.cell class="text-center">
                                    @if ($totalInvendable > 0)
                                        <flux:badge size="sm" color="red" inset="top bottom">{{ $totalInvendable }}</flux:badge>
                                    @else
                                        <span class="text-zinc-400 text-xs">0</span>
                                    @endif
                                </flux:table.cell>

                                {{-- État synthèse --}}
                                <flux:table.cell>
                                    @php
                                        $synthese = match(true) {
                                            $pct >= 100  => ['color' => 'green',  'label' => __('Complet')],
                                            $pct > 0     => ['color' => 'yellow', 'label' => __('Partiel')],
                                            default      => ['color' => 'zinc',   'label' => __('En attente')],
                                        };
                                    @endphp
                                    <flux:badge size="sm" :color="$synthese['color']" inset="top bottom">
                                        {{ $synthese['label'] }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>

                            {{-- Lignes réceptions de ce produit (niveau 3) --}}
                            @forelse ($receptionsDetail as $reception)
                                <flux:table.row
                                    wire:key="reception-{{ $reception->id }}"
                                    class="bg-blue-50/20 dark:bg-blue-900/10"
                                >
                                    {{-- Indentation niveau 3 --}}
                                    <flux:table.cell>
                                        <div class="w-3 border-b border-l border-blue-200 dark:border-blue-800 h-4 ml-6 rounded-bl"></div>
                                    </flux:table.cell>

                                    {{-- Date réception --}}
                                    <flux:table.cell class="text-xs text-zinc-500 whitespace-nowrap">
                                        {{ $reception->created_at->format('d/m/Y') }}
                                        <div class="text-zinc-400">{{ $reception->created_at->format('H:i') }}</div>
                                    </flux:table.cell>

                                    {{-- Référence réception --}}
                                    <flux:table.cell colspan="2">
                                        <p class="text-xs text-zinc-500 pl-2">
                                            {{ __('Réception') }} #{{ $reception->id }}
                                        </p>
                                    </flux:table.cell>

                                    {{-- Livraison : vide --}}
                                    <flux:table.cell class="hidden md:table-cell"></flux:table.cell>

                                    {{-- Reçu --}}
                                    <flux:table.cell class="hidden sm:table-cell text-center">
                                        @php
                                            $pctLine = $detail->quantite > 0
                                                ? round(($reception->recu / $detail->quantite) * 100)
                                                : 0;
                                        @endphp
                                        <div class="text-xs font-medium">{{ $reception->recu }} / {{ $detail->quantite }}</div>
                                        <div class="text-xs text-zinc-400">{{ $pctLine }}%</div>
                                    </flux:table.cell>

                                    {{-- Montant : vide --}}
                                    <flux:table.cell class="hidden md:table-cell"></flux:table.cell>

                                    {{-- Invendable --}}
                                    <flux:table.cell class="text-center">
                                        @if ($reception->invendable > 0)
                                            <flux:badge size="sm" color="red" inset="top bottom">{{ $reception->invendable }}</flux:badge>
                                        @else
                                            <span class="text-zinc-400 text-xs">0</span>
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
                                <flux:table.row wire:key="detail-{{ $detail->id }}-no-reception">
                                    <flux:table.cell colspan="9">
                                        <p class="text-xs text-center text-zinc-400 py-1 pl-10">
                                            {{ __('Aucune réception pour ce produit') }}
                                        </p>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse

                        @empty
                            <flux:table.row wire:key="bon-{{ $bon->id }}-no-detail">
                                <flux:table.cell colspan="9">
                                    <p class="text-xs text-center text-zinc-400 py-2">
                                        {{ __('Aucun produit dans cette commande') }}
                                    </p>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    @endif

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon name="inbox" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterState !== '')
                                        {{ __('Aucun bon de commande trouvé pour ces filtres') }}
                                    @else
                                        {{ __('Aucun bon de commande enregistré') }}
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
