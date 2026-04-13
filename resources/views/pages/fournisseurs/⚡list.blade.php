<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Fournisseur;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy        = 'name';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'asc';

    #[Url(as: 'q', except: '')]
    public string $search        = '';

    #[Url(as: 'etat', except: '')]
    public string $filterState   = '';

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    public $updatingSupplierId = null;

    // Bulk selection
    public array $selectedIds = [];
    public bool  $selectAll   = false;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void      { $this->resetPage(); $this->clearSelection(); }
    public function updatedPerPage(): void     { $this->resetPage(); $this->clearSelection(); }
    public function updatedFilterState(): void { $this->resetPage(); $this->clearSelection(); }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedIds = $this->fournisseurs->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updatedSelectedIds(): void
    {
        $pageIds = $this->fournisseurs->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->selectAll = !empty($pageIds) && empty(array_diff($pageIds, $this->selectedIds));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll   = false;
    }

    #[On('fournisseur-created')]
    #[On('fournisseur-updated')]
    #[On('fournisseur-deleted')]
    public function refresh(): void
    {
        unset($this->fournisseurs);
        $this->resetPage();
        $this->clearSelection();
    }

    public function edit(int $id): void
    {
        $this->dispatch('edit-fournisseur', id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-fournisseur', id: $id);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterState', 'perPage']);
        $this->resetPage();
        $this->clearSelection();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    public function toggleState(int $id): void
    {
        $this->updatingSupplierId = $id;

        try {
            DB::beginTransaction();

            $fournisseur = Fournisseur::findOrFail($id);
            $newState    = $fournisseur->state == 1 ? 0 : 1;

            $fournisseur->state = $newState;
            $fournisseur->save();

            DB::commit();

            unset($this->fournisseurs);

            $this->dispatch('fournisseur-state-updated', id: $id, state: $newState);
            $this->dispatch('fournisseur-updated');

            Flux::toast(
                heading: $newState == 1 ? 'Fournisseur activé' : 'Fournisseur désactivé',
                text: "Le fournisseur \"{$fournisseur->name}\" a été " . ($newState == 1 ? "activé" : "désactivé") . " avec succès",
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
            $this->updatingSupplierId = null;
        }
    }

    // ─── Bulk Actions ──────────────────────────────────────────────────────────

    public function bulkActivate(): void
    {
        if (empty($this->selectedIds)) return;

        try {
            DB::beginTransaction();
            $count = Fournisseur::whereIn('id', $this->selectedIds)->update(['state' => 1]);
            DB::commit();

            unset($this->fournisseurs);
            $this->clearSelection();
            $this->dispatch('fournisseur-updated');

            Flux::toast(
                heading: 'Fournisseurs activés',
                text: "{$count} fournisseur(s) ont été activés avec succès",
                variant: 'success'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                heading: 'Erreur',
                text: "Impossible d'activer les fournisseurs: " . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function bulkDeactivate(): void
    {
        if (empty($this->selectedIds)) return;

        try {
            DB::beginTransaction();
            $count = Fournisseur::whereIn('id', $this->selectedIds)->update(['state' => 0]);
            DB::commit();

            unset($this->fournisseurs);
            $this->clearSelection();
            $this->dispatch('fournisseur-updated');

            Flux::toast(
                heading: 'Fournisseurs désactivés',
                text: "{$count} fournisseur(s) ont été désactivés avec succès",
                variant: 'success'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de désactiver les fournisseurs: " . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) return;

        try {
            DB::beginTransaction();

            $blockedIds = DB::table('commande')
                ->whereIn('fournisseur_id', $this->selectedIds)
                ->pluck('fournisseur_id')
                ->unique()
                ->values()
                ->toArray();

            $deletableIds = array_values(array_diff($this->selectedIds, array_map('strval', $blockedIds)));

            $deleted = 0;
            if (!empty($deletableIds)) {
                $deleted = Fournisseur::whereIn('id', $deletableIds)->delete();
            }

            DB::commit();

            unset($this->fournisseurs);
            $this->clearSelection();
            $this->dispatch('fournisseur-deleted');

            if (!empty($blockedIds) && $deleted === 0) {
                $blockedNames = Fournisseur::whereIn('id', $blockedIds)->pluck('name')->join(', ');
                Flux::toast(
                    heading: 'Suppression impossible',
                    text: "Tous les fournisseurs sélectionnés ont des commandes associées et ne peuvent pas être supprimés : {$blockedNames}",
                    variant: 'danger'
                );
            } elseif (!empty($blockedIds)) {
                $blockedNames = Fournisseur::whereIn('id', $blockedIds)->pluck('name')->join(', ');
                Flux::toast(
                    heading: 'Suppression partielle',
                    text: "{$deleted} fournisseur(s) supprimé(s). Les suivants ont des commandes liées et n'ont pas été supprimés : {$blockedNames}",
                    variant: 'warning'
                );
            } else {
                Flux::toast(
                    heading: 'Fournisseurs supprimés',
                    text: "{$deleted} fournisseur(s) ont été supprimés avec succès",
                    variant: 'success'
                );
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de supprimer les fournisseurs : " . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function showDetails(int $id): void
    {
        $this->dispatch('view-fournisseur', id: $id);
    }

    #[Computed]
    public function fournisseurs()
    {
        return Fournisseur::query()
            ->when($this->search, fn($q) =>
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('raison_social', 'like', "%{$this->search}%")
                ->orWhere('ville', 'like', "%{$this->search}%")
                ->orWhere('mail', 'like', "%{$this->search}%")
            )
            ->when($this->filterState !== '', fn($q) =>
            $q->where('state', $this->filterState)
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
                placeholder="Rechercher un fournisseur..."
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

        <flux:modal.trigger name="create-fournisseur">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter un fournisseur
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Filtres -->
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:radio.group wire:model.live="filterState" variant="segmented">
            <flux:radio label="Tous" value="" />
            <flux:radio label="Actif" value="1" />
            <flux:radio label="Inactif" value="0" />
        </flux:radio.group>

        @if ($search || $filterState !== '' || $perPage !== 10)
            <flux:button variant="danger" size="sm" wire:click="resetFilters" icon="arrow-path" class="w-full sm:w-auto">
                Réinitialiser
            </flux:button>
        @endif
    </div>

    <!-- Barre d'actions bulk avec wire:transition -->
    @if (!empty($selectedIds))
        <div
            wire:transition
            class="flex items-center gap-3 mb-4 px-4 py-3 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-lg"
        >
            <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                {{ count($selectedIds) }} sélectionné(s)
            </span>

            <div class="flex items-center gap-2 ml-auto flex-wrap">
                <flux:button
                    size="sm"
                    variant="filled"
                    icon="check-circle"
                    wire:click="bulkActivate"
                    wire:confirm="Activer les {{ count($selectedIds) }} fournisseur(s) sélectionné(s) ?"
                >
                    Activer
                </flux:button>

                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="x-circle"
                    wire:click="bulkDeactivate"
                    wire:confirm="Désactiver les {{ count($selectedIds) }} fournisseur(s) sélectionné(s) ?"
                >
                    Désactiver
                </flux:button>

                <flux:button
                    size="sm"
                    variant="danger"
                    icon="trash"
                    wire:click="bulkDelete"
                    wire:confirm="Supprimer définitivement les {{ count($selectedIds) }} fournisseur(s) sélectionné(s) ? Cette action est irréversible."
                >
                    Supprimer
                </flux:button>

                <flux:button size="sm" variant="ghost" wire:click="clearSelection">
                    Annuler
                </flux:button>
            </div>
        </div>
    @endif

    <flux:card class="p-5">
        <!-- Table -->
        <flux:table :paginate="$this->fournisseurs" variant="bordered">
            <flux:table.columns>
                <flux:table.column class="w-10">
                    <flux:checkbox
                        wire:model.live="selectAll"
                        :disabled="$this->fournisseurs->isEmpty()"
                    />
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'code'"
                    :direction="$sortDirection"
                    wire:click="sort('code')"
                >
                    Code
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Nom
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'raison_social'"
                    :direction="$sortDirection"
                    wire:click="sort('raison_social')"
                    class="hidden md:table-cell"
                >
                    Raison sociale
                </flux:table.column>

                <flux:table.column class="hidden lg:table-cell">
                    Contact
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'ville'"
                    :direction="$sortDirection"
                    wire:click="sort('ville')"
                    class="hidden sm:table-cell"
                >
                    Ville
                </flux:table.column>

                <flux:table.column class="text-center">
                    État
                </flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->fournisseurs as $fournisseur)
                    <flux:table.row
                        :key="$fournisseur->id"
                        wire:key="fournisseur-{{ $fournisseur->id }}"
                    >
                        <!-- Checkbox -->
                        <flux:table.cell>
                            <flux:checkbox
                                value="{{ $fournisseur->id }}"
                                wire:model.live="selectedIds"
                            />
                        </flux:table.cell>

                        <!-- Code -->
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $fournisseur->code }}
                            </flux:badge>
                        </flux:table.cell>

                        <!-- Nom -->
                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $fournisseur->name }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5 md:hidden">
                                {{ $fournisseur->raison_social ?? '—' }}
                            </p>
                            <p class="text-xs text-zinc-400 sm:hidden">
                                {{ $fournisseur->ville ?? '—' }}
                            </p>
                            @if($fournisseur->telephone || $fournisseur->mail)
                                <div class="text-xs text-zinc-400 mt-1 lg:hidden">
                                    @if($fournisseur->telephone)
                                        <div>Tél: {{ $fournisseur->telephone }}</div>
                                    @endif
                                    @if($fournisseur->mail)
                                        <div>Mail: {{ $fournisseur->mail }}</div>
                                    @endif
                                </div>
                            @endif
                        </flux:table.cell>

                        <!-- Raison sociale -->
                        <flux:table.cell class="hidden md:table-cell text-zinc-400 text-sm">
                            {{ $fournisseur->raison_social ?? '—' }}
                        </flux:table.cell>

                        <!-- Contact -->
                        <flux:table.cell class="hidden lg:table-cell">
                            <div class="space-y-0.5">
                                @if ($fournisseur->mail)
                                    <p class="text-xs text-zinc-400">{{ $fournisseur->mail }}</p>
                                @endif
                                @if ($fournisseur->telephone)
                                    <p class="text-xs text-zinc-400">{{ $fournisseur->telephone }}</p>
                                @endif
                                @if (!$fournisseur->mail && !$fournisseur->telephone)
                                    <span class="text-zinc-400 text-sm">—</span>
                                @endif
                            </div>
                        </flux:table.cell>

                        <!-- Ville -->
                        <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm">
                            {{ $fournisseur->ville ?? '—' }}
                        </flux:table.cell>

                        <!-- État avec Toggle -->
                        <flux:table.cell class="text-center">
                            <div class="flex items-center justify-center">
                                @if($updatingSupplierId === $fournisseur->id)
                                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                @else
                                    <button
                                        wire:click="toggleState({{ $fournisseur->id }})"
                                        type="button"
                                        role="switch"
                                        aria-checked="{{ $fournisseur->state == 1 ? 'true' : 'false' }}"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:opacity-80"
                                        style="background-color: {{ $fournisseur->state == 1 ? '#22c55e' : '#d1d5db' }}"
                                    >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        style="transform: translateX({{ $fournisseur->state == 1 ? '24px' : '4px' }})"
                                    />
                                    </button>
                                @endif
                            </div>
                            <span class="sr-only">{{ $fournisseur->state == 1 ? 'Actif' : 'Inactif' }}</span>
                        </flux:table.cell>

                        <!-- Actions -->
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="showDetails({{ $fournisseur->id }})">
                                        Voir les détails
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="pencil" wire:click="edit({{ $fournisseur->id }})">
                                        Modifier
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $fournisseur->id }})">
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
                                <flux:icon name="building-storefront" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterState !== '')
                                        Aucun fournisseur trouvé pour ces filtres
                                    @else
                                        Aucun fournisseur enregistré
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
    <livewire:pages::fournisseurs.create />
    <livewire:pages::fournisseurs.edit />
    <livewire:pages::fournisseurs.delete />
    <livewire:pages::fournisseurs.view />
</div>
