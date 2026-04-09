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

    // Propriété pour suivre l'ID du fournisseur en cours de mise à jour
    public $updatingSupplierId = null;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedPerPage(): void      { $this->resetPage(); }
    public function updatedFilterState(): void  { $this->resetPage(); }

    #[On('fournisseur-created')]
    #[On('fournisseur-updated')]
    #[On('fournisseur-deleted')]
    public function refresh(): void
    {
        unset($this->fournisseurs);
        $this->resetPage();
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

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    public function toggleState(int $id): void
    {
        // Définir l'ID du fournisseur en cours de mise à jour
        $this->updatingSupplierId = $id;

        try {
            DB::beginTransaction();

            // Chercher le fournisseur par son ID
            $fournisseur = Fournisseur::findOrFail($id);
            $oldState = $fournisseur->state;
            $newState = $oldState == 1 ? 0 : 1;

            // Mettre à jour l'état
            $fournisseur->state = $newState;
            $fournisseur->save();

            DB::commit();

            // Rafraîchir la propriété computed
            unset($this->fournisseurs);

            // Dispatch des événements
            $this->dispatch('fournisseur-state-updated', id: $id, state: $newState);
            $this->dispatch('fournisseur-updated');

            // Afficher le toast de succès
            Flux::toast(
                heading: $newState == 1 ? 'Fournisseur activé' : 'Fournisseur désactivé',
                text: "Le fournisseur \"{$fournisseur->name}\" a été " . ($newState == 1 ? "activé" : "désactivé") . " avec succès",
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            // Afficher le toast d'erreur
            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier l'état du fournisseur: " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            // Réinitialiser l'ID du fournisseur en cours de mise à jour
            $this->updatingSupplierId = null;
        }
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

    <!-- Table -->
    <flux:table :paginate="$this->fournisseurs" variant="bordered">
        <flux:table.columns>
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
                <flux:table.row :key="$fournisseur->id" wire:key="fournisseur-{{ $fournisseur->id }}">

                    <!-- Code -->
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $fournisseur->code }}
                        </flux:badge>
                    </flux:table.cell>

                    <!-- Nom -->
                    <flux:table.cell>
                        <p class="font-medium text-sm">{{ $fournisseur->name }}</p>
                        <!-- Infos visibles en mobile uniquement -->
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

                    <!-- Raison sociale cachée en mobile -->
                    <flux:table.cell class="hidden md:table-cell text-zinc-400 text-sm">
                        {{ $fournisseur->raison_social ?? '—' }}
                    </flux:table.cell>

                    <!-- Contact caché en mobile/tablet -->
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

                    <!-- Ville cachée en mobile -->
                    <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm">
                        {{ $fournisseur->ville ?? '—' }}
                    </flux:table.cell>

                    <!-- État avec Toggle -->
                    <flux:table.cell class="text-center">
                        <div class="flex items-center justify-center">
                            <!-- Afficher le loading seulement pour cette ligne -->
                            @if($updatingSupplierId === $fournisseur->id)
                                <div class="flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
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

                        <span class="sr-only">
                            {{ $fournisseur->state == 1 ? 'Actif' : 'Inactif' }}
                        </span>
                    </flux:table.cell>

                    <!-- Actions -->
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item
                                    icon="eye"
                                    wire:navigate
                                    :href="route('fournisseurs.view', $fournisseur->id)"
                                >
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
                    <flux:table.cell colspan="7">
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

    <livewire:pages::fournisseurs.create />
    <livewire:pages::fournisseurs.edit />
    <livewire:pages::fournisseurs.delete />
</div>
