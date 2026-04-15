<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Magasin;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

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

    public bool $showFilters = false;
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
        $this->showFilters = ! $this->showFilters;
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

    public function updateStatus(int $id, int $newStatus): void
    {
        $this->updatingStatus[$id] = true;

        try {
            DB::beginTransaction();

            $commande = Commande::findOrFail($id);
            $oldStatus = $commande->status;

            // Vérifier la transition de statut
            $validTransitions = [
                1 => [2, -1],  // Créée -> Facturée ou Annulée
                2 => [3, -1],  // Facturée -> Clôturée ou Annulée
                3 => [],       // Clôturée -> aucune transition
                -1 => [],      // Annulée -> aucune transition
            ];

            if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
                $statusLabels = [
                    -1 => 'Annulée',
                    1 => 'Créée',
                    2 => 'Facturée',
                    3 => 'Clôturée'
                ];

                Flux::toast(
                    heading: 'Transition invalide',
                    text: "Impossible de passer de \"{$statusLabels[$oldStatus]}\" à \"{$statusLabels[$newStatus]}\"",
                    variant: 'warning'
                );
                return;
            }

            $commande->status = $newStatus;

            // Si la commande est clôturée ou annulée, on peut ajouter une date
            if ($newStatus === 3) {
                $commande->date_cloture = now();
            } elseif ($newStatus === 2) {
                $commande->date_facturation = now();
            } elseif ($newStatus === -1) {
                $commande->date_annulation = now();
            }

            $commande->save();

            DB::commit();

            unset($this->commandes);
            unset($this->stats);

            $this->dispatch('commande-updated');

            $statusLabels = [
                -1 => 'Annulée',
                1 => 'Créée',
                2 => 'Facturée',
                3 => 'Clôturée'
            ];

            Flux::toast(
                heading: 'Statut mis à jour',
                text: "La commande \"{$commande->libelle}\" est maintenant \"{$statusLabels[$newStatus]}\"",
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier le statut : " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            unset($this->updatingStatus[$id]);
        }
    }

    public function getNextStatus(int $currentStatus): ?int
    {
        $nextStatus = match($currentStatus) {
            1 => 2,  // Créée -> Facturée
            2 => 3,  // Facturée -> Clôturée
            default => null,
        };
        return $nextStatus;
    }

    public function getStatusLabel(int $status): string
    {
        return match($status) {
            -1 => 'Annulée',
            1 => 'Créée',
            2 => 'Facturée',
            3 => 'Clôturée',
            default => '—',
        };
    }

    public function getStatusColor(int $status): string
    {
        return match($status) {
            -1 => 'red',
            1 => 'blue',
            2 => 'yellow',
            3 => 'green',
            default => 'zinc',
        };
    }

    #[On('commande-created')]
    #[On('commande-updated')]
    #[On('commande-deleted')]
    public function refresh(): void
    {
        unset($this->commandes);
        unset($this->stats);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
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
    public function stats()
    {
        $base = $this->statsQuery();

        return [
            'total'      => (clone $base)->count(),
            'crees'      => (clone $base)->where('status', 1)->count(),
            'facturees'  => (clone $base)->where('status', 2)->count(),
            'montant'    => (clone $base)->sum('montant_total'),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->filterStatus, $this->filterEtat, $this->filterFournisseur, $this->filterDateFrom, $this->filterDateTo])
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
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Commande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Liste</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Heading + bouton -->
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Commandes') }}</flux:heading>

        <flux:modal.trigger name="create-commande">
            <flux:button variant="primary" class="w-full sm:w-auto" href="{{ route('orders.create') }}" wire:navigate>
                Ajouter une commande
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Total Commandes</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Créées</p>
            <p class="text-3xl font-bold mt-1 text-blue-500">{{ $this->stats['crees'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Facturées</p>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['facturees'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Montant total</p>
            <p class="text-2xl font-bold mt-1 text-zinc-700 dark:text-zinc-200">
                {{ number_format($this->stats['montant'], 2, ',', ' ') }} €
            </p>
        </flux:card>
    </div>

    <flux:card class="p-5">

        <!-- En-tête tableau : recherche | toggle filtres | per page -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live="search"
                    placeholder="Rechercher une commande..."
                    icon="magnifying-glass"
                    class="w-full sm:w-80"
                />

                <!-- Bouton toggle filtres avec badge compteur -->
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

        <!-- Panneau de filtres (togglable) -->
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
                    <!-- Filtre statut -->
                    <flux:radio.group wire:model.live="filterStatus" variant="segmented">
                        <flux:radio label="Tous"      value=""   />
                        <flux:radio label="Annulée"   value="-1" />
                        <flux:radio label="Créée"     value="1"  />
                        <flux:radio label="Facturée"  value="2"  />
                        <flux:radio label="Clôturée"  value="3"  />
                    </flux:radio.group>

                    <!-- Filtre état (enum) -->
                    <flux:radio.group wire:model.live="filterEtat" variant="segmented">
                        <flux:radio label="Tous"          value=""            />
                        <flux:radio label="Pré-commande"  value="pre_commande" />
                        <flux:radio label="Commande"      value="commande"    />
                    </flux:radio.group>

                    <!-- Filtre fournisseur -->
                    <flux:select wire:model.live="filterFournisseur" class="w-full sm:w-56">
                        <flux:select.option value="">Tous les fournisseurs</flux:select.option>
                        @foreach ($this->fournisseurs as $fournisseur)
                            <flux:select.option value="{{ $fournisseur->id }}">{{ $fournisseur->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <!-- Filtre date -->
                    <div class="flex items-center gap-2">
                        <flux:input
                            type="date"
                            wire:model.live="filterDateFrom"
                            label="Du"
                            class="w-40"
                        />
                        <span class="text-zinc-400 text-sm mt-5">→</span>
                        <flux:input
                            type="date"
                            wire:model.live="filterDateTo"
                            label="Au"
                            class="w-40"
                        />
                    </div>
                </div>
            </div>
        @endif

        <!-- Table -->
        <flux:table :paginate="$this->commandes" variant="bordered">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'libelle'"
                    :direction="$sortDirection"
                    wire:click="sort('libelle')"
                >
                    Libellé
                </flux:table.column>

                <flux:table.column class="hidden sm:table-cell">
                    Fournisseur
                </flux:table.column>

                <flux:table.column class="hidden md:table-cell">
                    Magasin livraison
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'montant_total'"
                    :direction="$sortDirection"
                    wire:click="sort('montant_total')"
                    class="hidden lg:table-cell"
                >
                    Montant
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'status'"
                    :direction="$sortDirection"
                    wire:click="sort('status')"
                >
                    Statut
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                    class="hidden sm:table-cell"
                >
                    Date
                </flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->commandes as $commande)
                    <flux:table.row :key="$commande->id" wire:key="commande-{{ $commande->id }}">

                        <!-- Libellé -->
                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $commande->libelle ?? '—' }}</p>
                            <!-- Fournisseur visible en mobile -->
                            <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                                {{ $commande->fournisseur?->name ?? '—' }}
                            </p>
                            <!-- Date visible en mobile -->
                            <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                                {{ $commande->created_at->translatedFormat('d F Y') }}
                            </p>
                        </flux:table.cell>

                        <!-- Fournisseur -->
                        <flux:table.cell class="hidden sm:table-cell">
                            @if ($commande->fournisseur)
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $commande->fournisseur->name }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <!-- Magasin livraison -->
                        <flux:table.cell class="hidden md:table-cell">
                            @if ($commande->magasinLivraison)
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $commande->magasinLivraison->name }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <!-- Montant -->
                        <flux:table.cell class="hidden lg:table-cell text-sm font-medium whitespace-nowrap">
                            {{ number_format($commande->montant_total, 2, ',', ' ') }} €
                        </flux:table.cell>

                        <!-- Statut avec Toggle -->
                        <flux:table.cell>
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    @php
                                        $nextStatus = $this->getNextStatus($commande->status);
                                    @endphp

                                    @if($nextStatus && !isset($updatingStatus[$commande->id]))
                                        <button
                                            wire:click="updateStatus({{ $commande->id }}, {{ $nextStatus }})"
                                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium transition-colors"
                                            style="background-color: {{ $this->getStatusColor($commande->status) === 'green' ? '#22c55e20' : '#3b82f620' }}; color: {{ $this->getStatusColor($commande->status) === 'green' ? '#16a34a' : '#2563eb' }}"
                                        >
                                            <flux:icon name="arrow-path" class="size-3" />
                                            Passer à {{ $this->getStatusLabel($nextStatus) }}
                                        </button>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <flux:badge size="sm" :color="$this->getStatusColor($commande->status)">
                                        {{ $this->getStatusLabel($commande->status) }}
                                    </flux:badge>
                                    @if ($commande->etat)
                                        <flux:badge size="sm" color="purple">
                                            {{ $commande->etat === 'pre_commande' ? 'Pré-commande' : 'Commande' }}
                                        </flux:badge>
                                    @endif
                                </div>

                                @if(isset($updatingStatus[$commande->id]))
                                    <div class="flex items-center gap-1 text-xs text-zinc-400">
                                        <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        Mise à jour...
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <!-- Date -->
                        <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm whitespace-nowrap">
                            {{ $commande->created_at->translatedFormat('d F Y') }}
                        </flux:table.cell>

                        <!-- Actions -->
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="document-text" wire:click="showBonCommande({{ $commande->id }})">
                                        Détails de la commande
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil" href="{{ route('orders.edit', $commande->id) }}">
                                        Modifier
                                    </flux:menu.item>
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
                        <flux:table.cell colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon name="shopping-cart" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterStatus !== '' || $filterFournisseur !== '')
                                        Aucune commande trouvée pour ces filtres
                                    @else
                                        Aucune commande enregistrée
                                    @endif
                                </p>
                                @if ($search || $filterStatus !== '' || $filterFournisseur !== '')
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
