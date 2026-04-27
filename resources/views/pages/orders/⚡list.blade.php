<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Facture;
use App\Enums\CommandeStatus;
use App\Enums\CommandeEtat;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use App\Models\DetailFacture;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy        = 'created_at';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'desc';

    #[Url(as: 'q', except: '')]
    public string $search        = '';

    #[Url(as: 'statut', except: '')]
    public string $filterStatus  = '';

    #[Url(as: 'etat', except: '')]
    public string $filterEtat    = '';

    #[Url(as: 'fournisseur', except: '')]
    public string $filterFournisseur = '';

    #[Url(as: 'du', except: '')]
    public string $filterDateFrom = '';

    #[Url(as: 'au', except: '')]
    public string $filterDateTo   = '';

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    public bool  $showFilters    = false;
    public array $updatingStatus = [];

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void            { $this->resetPage(); }
    public function updatedPerPage(): void           { $this->resetPage(); }
    public function updatedFilterStatus(): void      { $this->resetPage(); }
    public function updatedFilterEtat(): void        { $this->resetPage(); }
    public function updatedFilterFournisseur(): void { $this->resetPage(); }
    public function updatedFilterDateFrom(): void    { $this->resetPage(); }
    public function updatedFilterDateTo(): void      { $this->resetPage(); }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterEtat', 'filterFournisseur', 'filterDateFrom', 'filterDateTo', 'perPage']);
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    private function generateFactureNumber(): string
    {
        $year  = date('Y');
        $month = date('m');

        $count = Facture::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count() + 1;

        return sprintf('FAC-%s%s-%04d', $year, $month, $count);
    }

    public function updateStatus(int $id, int $newStatusValue): void
    {
        $this->updatingStatus[$id] = true;

        try {
            DB::beginTransaction();

            $commande  = Commande::with(['fournisseur', 'detailsCommande'])->findOrFail($id);
            $oldStatus = $commande->status;
            $newStatus = CommandeStatus::from($newStatusValue);

            $validTransitions = [
                CommandeStatus::Cree->value      => [CommandeStatus::Facturee, CommandeStatus::Annulee],
                CommandeStatus::Facturee->value  => [CommandeStatus::Cloturee, CommandeStatus::Annulee],
                CommandeStatus::Cloturee->value  => [],
                CommandeStatus::Recue->value     => [],
                CommandeStatus::Annulee->value   => [],
            ];

            $allowed = $validTransitions[$oldStatus->value] ?? [];

            if (!in_array($newStatus, $allowed)) {
                Flux::toast(
                    heading: 'Transition invalide',
                    text: "Impossible de passer de \"{$oldStatus->label()}\" à \"{$newStatus->label()}\"",
                    variant: 'warning'
                );
                return;
            }

            $commande->status = $newStatus;

            if ($newStatus === CommandeStatus::Facturee) {
                $commande->date_facturation = now();

                $factureNumber = $this->generateFactureNumber();

                $montantTotal = $commande->detailsCommande->sum(function ($detail) {
                    $montantHT      = $detail->quantite * $detail->pu_achat_HT;
                    $montantRemise  = $montantHT * (($detail->taux_remise ?? 0) / 100);
                    $montantFinalHT = $montantHT - $montantRemise;
                    return $montantFinalHT * (1 + (($detail->tax ?? 0) / 100));
                });

                $facture = Facture::create([
                    'fournisseur_id' => $commande->fournisseur_id,
                    'type'           => 'achat',
                    'libelle'        => 'Facture ' . $commande->libelle,
                    'numero'         => $factureNumber,
                    'date_commande'  => $commande->created_at,
                    'montant'        => $montantTotal,
                    'date_reception' => null,
                    'commande_id'    => $commande->id,
                    'remise'         => $commande->remise_facture ?? 0,
                    'tax'            => 0,
                    'state'          => 1,
                ]);

                foreach ($commande->detailsCommande as $detail) {
                    $montantHT       = $detail->quantite * $detail->pu_achat_HT;
                    $tauxRemise      = $detail->taux_remise ?? 0;
                    $tauxTax         = $detail->tax ?? 0;

                    $montantRemise   = $montantHT * ($tauxRemise / 100);
                    $montantFinalHT  = $montantHT - $montantRemise;
                    $montantFinalNet = $montantFinalHT * (1 + ($tauxTax / 100));

                    DetailFacture::create([
                        'facture_id'         => $facture->id,
                        'detail_commande_id' => $detail->id,
                        'quantite_commande'  => $detail->quantite,
                        'montant_HT'         => round($montantHT, 2),
                        'montant_remise'     => round($montantRemise, 2),
                        'montant_final_ht'   => round($montantFinalHT, 2),
                        'montant_final_net'  => round($montantFinalNet, 2),
                        'state'              => 1,
                    ]);
                }

                $this->dispatch('facture-created', facture: $facture);

                Flux::toast(
                    heading: 'Facture créée',
                    text: "La facture N°{$factureNumber} a été générée automatiquement",
                    variant: 'success'
                );

            } elseif ($newStatus === CommandeStatus::Cloturee) {
                $commande->date_cloture = now();
                $commande->etat         = CommandeEtat::Commande;

            } elseif ($newStatus === CommandeStatus::Annulee) {
                $commande->date_annulation = now();

                $facture = Facture::where('commande_id', $commande->id)->first();
                if ($facture && $facture->state == 1) {
                    $facture->update(['state' => 0]);

                    Flux::toast(
                        heading: 'Facture annulée',
                        text: 'La facture associée a été annulée',
                        variant: 'warning'
                    );
                }
            }

            $commande->save();

            DB::commit();

            unset($this->commandes);
            unset($this->stats);

            $this->dispatch('commande-updated');

            Flux::toast(
                heading: 'Statut mis à jour',
                text: "La commande \"{$commande->libelle}\" est maintenant \"{$newStatus->label()}\"",
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: 'Impossible de modifier le statut : ' . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            unset($this->updatingStatus[$id]);
        }
    }

    public function toggleCloture(int $id): void
    {
        $commande = Commande::findOrFail($id);

        if ($commande->status !== CommandeStatus::Facturee) {
            Flux::toast(
                heading: 'Action impossible',
                text: 'Seules les commandes facturées peuvent être clôturées',
                variant: 'warning'
            );
            return;
        }

        $this->updateStatus($id, CommandeStatus::Cloturee->value);
    }

    public function canEdit(CommandeStatus $status): bool
    {
        return !in_array($status, [
            CommandeStatus::Cloturee,
            CommandeStatus::Recue,
            CommandeStatus::Annulee,
        ]);
    }

    #[On('commande-created')]
    #[On('commande-updated')]
    #[On('commande-deleted')]
    #[On('facture-created')]
    public function refresh(): void
    {
        unset($this->commandes);
        unset($this->stats);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $commande = Commande::findOrFail($id);

        if (!$this->canEdit($commande->status)) {
            Flux::toast(
                heading: 'Action impossible',
                text: 'Seules les commandes avec le statut "Créée" peuvent être modifiées',
                variant: 'warning'
            );
            return;
        }

        $this->dispatch('edit-commande', id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-commande', id: $id);
    }

    private function baseQuery()
    {
        return Commande::query()
            ->when($this->search, fn($q) =>
            $q->where('libelle', 'like', "%{$this->search}%")
                ->orWhereHas('fournisseur', fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterStatus !== '', fn($q) =>
            $q->where('status', $this->filterStatus)
            )
            ->when($this->filterEtat !== '', fn($q) =>
            $q->where('etat', $this->filterEtat)
            )
            ->when($this->filterFournisseur !== '', fn($q) =>
            $q->where('fournisseur_id', $this->filterFournisseur)
            )
            ->when($this->filterDateFrom !== '', fn($q) =>
            $q->whereDate('created_at', '>=', $this->filterDateFrom)
            )
            ->when($this->filterDateTo !== '', fn($q) =>
            $q->whereDate('created_at', '<=', $this->filterDateTo)
            );
    }

    private function statsQuery()
    {
        return Commande::query()
            ->when($this->filterEtat !== '', fn($q) =>
            $q->where('etat', $this->filterEtat)
            )
            ->when($this->filterFournisseur !== '', fn($q) =>
            $q->where('fournisseur_id', $this->filterFournisseur)
            )
            ->when($this->filterDateFrom !== '', fn($q) =>
            $q->whereDate('created_at', '>=', $this->filterDateFrom)
            )
            ->when($this->filterDateTo !== '', fn($q) =>
            $q->whereDate('created_at', '<=', $this->filterDateTo)
            );
    }

    #[Computed]
    public function stats(): array
    {
        $base = $this->statsQuery();

        return [
            'total'     => (clone $base)->count(),
            'crees'     => (clone $base)->where('status', CommandeStatus::Cree->value)->count(),
            'facturees' => (clone $base)->where('status', CommandeStatus::Facturee->value)->count(),
            'cloturees' => (clone $base)->where('status', CommandeStatus::Cloturee->value)->count(),
            'montant'   => (clone $base)->sum('montant_total'),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([
            $this->filterStatus,
            $this->filterEtat,
            $this->filterFournisseur,
            $this->filterDateFrom,
            $this->filterDateTo,
        ])
            ->filter(fn($v) => $v !== '')
            ->count();
    }

    #[Computed]
    public function fournisseurs()
    {
        return Fournisseur::orderBy('name')->get();
    }

    #[Computed]
    public function commandes()
    {
        return $this->baseQuery()
            ->with(['fournisseur', 'magasinLivraison'])
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function showBonCommande(int $id): void
    {
        $this->dispatch('show-bon-commande', id: $id);
    }

    public function formatCurrency(?float $amount): string
    {
        return app(\App\Services\CurrencyService::class)->format($amount);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Précommande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Liste</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Précommande') }}</flux:heading>

        <flux:button variant="primary" class="w-full sm:w-auto" href="{{ route('orders.create') }}" wire:navigate>
            Nouvelle précommande
        </flux:button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Total Commandes</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">{{ CommandeStatus::Cree->label() }}</p>
            <p class="text-3xl font-bold mt-1 text-blue-500">{{ $this->stats['crees'] }}</p>
        </flux:card>
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">{{ CommandeStatus::Facturee->label() }}</p>
            <p class="text-3xl font-bold mt-1 text-amber-500">{{ $this->stats['facturees'] }}</p>
        </flux:card>
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">{{ CommandeStatus::Cloturee->label() }}</p>
            <p class="text-3xl font-bold mt-1 text-purple-500">{{ $this->stats['cloturees'] }}</p>
        </flux:card>
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Montant total</p>
            <p class="text-2xl font-bold mt-1 text-zinc-700 dark:text-zinc-200">
                {{ $this->formatCurrency($this->stats['montant']) }}
            </p>
        </flux:card>
    </div>

    <flux:card class="p-5">

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live="search"
                    placeholder="Rechercher une commande..."
                    icon="magnifying-glass"
                    class="w-full sm:w-80"
                />
                <div class="relative">
                    <flux:button
                        wire:click="toggleFilters"
                        :variant="$showFilters ? 'primary' : 'ghost'"
                        icon="funnel"
                        size="sm"
                    >
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

                    <flux:radio.group wire:model.live="filterStatus" variant="segmented">
                        <flux:radio label="Tous" value="" />
                        @foreach(CommandeStatus::cases() as $case)
                            <flux:radio label="{{ $case->label() }}" value="{{ $case->value }}" />
                        @endforeach
                    </flux:radio.group>

                    <flux:radio.group wire:model.live="filterEtat" variant="segmented">
                        <flux:radio label="Tous" value="" />
                        @foreach(CommandeEtat::cases() as $case)
                            <flux:radio label="{{ $case->label() }}" value="{{ $case->value }}" />
                        @endforeach
                    </flux:radio.group>

                    <flux:select wire:model.live="filterFournisseur" class="w-full sm:w-56">
                        <flux:select.option value="">Tous les fournisseurs</flux:select.option>
                        @foreach ($this->fournisseurs as $fournisseur)
                            <flux:select.option value="{{ $fournisseur->id }}">{{ $fournisseur->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex items-center gap-2">
                        <flux:input type="date" wire:model.live="filterDateFrom" label="Du" class="w-40" />
                        <span class="text-zinc-400 text-sm mt-5">→</span>
                        <flux:input type="date" wire:model.live="filterDateTo" label="Au" class="w-40" />
                    </div>
                </div>
            </div>
        @endif

        <flux:table :paginate="$this->commandes" variant="bordered">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'libelle'" :direction="$sortDirection" wire:click="sort('libelle')">
                    Libellé
                </flux:table.column>
                <flux:table.column class="hidden sm:table-cell">Fournisseur</flux:table.column>
                <flux:table.column class="hidden md:table-cell">Magasin livraison</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'montant_total'" :direction="$sortDirection" wire:click="sort('montant_total')" class="hidden lg:table-cell">
                    Montant
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">
                    Statut
                </flux:table.column>
                <flux:table.column class="hidden md:table-cell">
                    Action
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')" class="hidden sm:table-cell">
                    Date
                </flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->commandes as $commande)
                    <flux:table.row :key="$commande->id" wire:key="commande-{{ $commande->id }}">

                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $commande->libelle ?? '—' }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">{{ $commande->fournisseur?->name ?? '—' }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">{{ $commande->created_at->translatedFormat('d F Y') }}</p>
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell">
                            @if ($commande->fournisseur)
                                <span class="text-sm font-medium uppercase">{{ $commande->fournisseur->name }}</span>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell">
                            @if ($commande->magasinLivraison)
                                <span class="text-sm font-medium uppercase">{{ $commande->magasinLivraison->name }}</span>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="hidden lg:table-cell text-sm font-medium whitespace-nowrap">
                            {{ $this->formatCurrency($commande->montant_total) }}
                        </flux:table.cell>

                        {{-- Cellule Statut --}}
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-2">
                                <flux:badge size="sm" :color="$commande->status->color()">
                                    {{ $commande->status->label() }}
                                </flux:badge>

                                @if ($commande->etat)
                                    <flux:badge size="sm" :color="$commande->etat->color()">
                                        {{ $commande->etat->label() }}
                                    </flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Cellule Action --}}
                        <flux:table.cell class="hidden md:table-cell">

                            @if(isset($updatingStatus[$commande->id]))
                                <flux:button variant="ghost" size="sm" disabled>
                                    <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    Mise à jour...
                                </flux:button>

                                {{-- Créée → bouton "Facturer" --}}
                            @elseif($commande->status === CommandeStatus::Cree)
                                <flux:button
                                    variant="primary"
                                    color="blue"
                                    size="sm"
                                    icon="document-text"
                                    wire:click="updateStatus({{ $commande->id }}, {{ CommandeStatus::Facturee->value }})"
                                >
                                    {{ CommandeStatus::Facturee->label() }}
                                </flux:button>

                                {{-- Facturée → toggle pour clôturer --}}
                            @elseif($commande->status === CommandeStatus::Facturee)
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="toggleCloture({{ $commande->id }})"
                                        type="button"
                                        role="switch"
                                        aria-checked="false"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-zinc-300 transition-colors hover:bg-zinc-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:bg-zinc-600 dark:hover:bg-zinc-500"
                                    >
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform translate-x-1"></span>
                                        <span class="sr-only">Clôturer la commande</span>
                                    </button>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Clôturer</span>
                                </div>

                                {{-- Clôturée, Reçue, Annulée → tiret --}}
                            @else
                                <span class="text-zinc-400 text-xs">—</span>
                            @endif

                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm whitespace-nowrap">
                            {{ $commande->created_at->translatedFormat('d F Y') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
{{--                                    <flux:menu.item icon="document-text" wire:click="showBonCommande({{ $commande->id }})">--}}
{{--                                        Détails de la commande--}}
{{--                                    </flux:menu.item>--}}
                                    <flux:menu.item icon="pencil" href="{{ route('orders.view', $commande->id) }}" wire:navigate>
                                        Details
                                    </flux:menu.item>
{{--                                    @if(in_array($commande->status, [CommandeStatus::Facturee, CommandeStatus::Cloturee, CommandeStatus::Recue]))--}}
{{--                                        <flux:menu.item icon="receipt-percent" href="{{ route('orders.facture', $commande->id) }}" wire:navigate>--}}
{{--                                            Voir la facture--}}
{{--                                        </flux:menu.item>--}}
{{--                                    @endif--}}

                                    @if($this->canEdit($commande->status))
                                        <flux:menu.item icon="pencil" href="{{ route('orders.edit', $commande->id) }}" wire:navigate>
                                            Modifier
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item icon="pencil" disabled class="opacity-50 cursor-not-allowed">
                                            Modifier ({{ $commande->status->label() }})
                                        </flux:menu.item>
                                    @endif

                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $commande->id }})">
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
                                <flux:icon name="shopping-cart" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterStatus !== '' || $filterEtat !== '' || $filterFournisseur !== '' || $filterDateFrom !== '' || $filterDateTo !== '')
                                        Aucune commande trouvée pour ces filtres
                                    @else
                                        Aucune commande enregistrée
                                    @endif
                                </p>
                                @if ($search || $filterStatus !== '' || $filterEtat !== '' || $filterFournisseur !== '' || $filterDateFrom !== '' || $filterDateTo !== '')
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

    <livewire:pages::orders.partials.bon-commande />
</div>
