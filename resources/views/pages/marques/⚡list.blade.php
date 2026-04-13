<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Marque;
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

    public bool $showFilters = false;

    public $updatingMarqueId = null;

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

    public function toggleState(string $code): void
    {
        $this->updatingMarqueId = $code;

        try {
            DB::beginTransaction();

            $marque   = Marque::findOrFail($code);
            $newState = $marque->state == 1 ? 0 : 1;

            $marque->state = $newState;
            $marque->save();

            DB::commit();

            unset($this->marques);
            unset($this->stats);

            $this->dispatch('marque-updated');

            Flux::toast(
                heading: $newState == 1 ? 'Marque activée' : 'Marque désactivée',
                text: "La marque \"{$marque->name}\" a été " . ($newState == 1 ? 'activée' : 'désactivée') . ' avec succès',
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier l'état de la marque : " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            $this->updatingMarqueId = null;
        }
    }

    #[On('marque-created')]
    #[On('marque-updated')]
    #[On('marque-deleted')]
    public function refresh(): void
    {
        unset($this->marques);
        unset($this->stats);
        $this->resetPage();
    }

    public function edit(string $code): void
    {
        $this->dispatch('edit-marque', code: $code);
    }

    public function confirmDelete(string $code): void
    {
        $this->dispatch('delete-marque', code: $code);
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
    public function stats()
    {
        return [
            'total'    => Marque::count(),
            'active'   => Marque::where('state', 1)->count(),
            'inactive' => Marque::where('state', 0)->count(),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->filterState])
            ->filter(fn($v) => $v !== '')
            ->count();
    }

    #[Computed]
    public function marques()
    {
        return Marque::query()
            ->withCount('categories')
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filterState !== '', fn($q) => $q->byState($this->filterState))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Marque</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Liste</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Heading + bouton -->
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Marques') }}</flux:heading>

        <flux:modal.trigger name="create-marque">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter une marque
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Total Marques</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Marques Actives</p>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['active'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Marques Inactives</p>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['inactive'] }}</p>
        </flux:card>
    </div>

    <flux:card class="p-5">

        <!-- En-tête tableau : recherche | toggle filtres | per page -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher une marque..."
                    icon="magnifying-glass"
                    class="w-full sm:w-72"
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
                    <flux:radio.group wire:model.live="filterState" variant="segmented">
                        <flux:radio label="Tous"    value=""  />
                        <flux:radio label="Actif"   value="1" />
                        <flux:radio label="Inactif" value="0" />
                    </flux:radio.group>
                </div>
            </div>
        @endif

        <!-- Table -->
        <flux:table :paginate="$this->marques" variant="bordered">
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

                <flux:table.column class="hidden sm:table-cell">Catégories</flux:table.column>

                <flux:table.column class="text-center">État</flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->marques as $marque)
                    <flux:table.row :key="$marque->code" wire:key="marque-{{ $marque->code }}">

                        <!-- Code -->
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $marque->code }}
                            </flux:badge>
                        </flux:table.cell>

                        <!-- Nom -->
                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $marque->name }}</p>
                            <p class="text-xs text-zinc-400 mt-0.5 sm:hidden">
                                {{ $marque->categories_count }} catégorie{{ $marque->categories_count > 1 ? 's' : '' }}
                            </p>
                        </flux:table.cell>

                        <!-- Catégories -->
                        <flux:table.cell class="hidden sm:table-cell">
                            <flux:badge size="sm" color="blue" inset="top bottom">
                                {{ $marque->categories_count }} catégorie{{ $marque->categories_count > 1 ? 's' : '' }}
                            </flux:badge>
                        </flux:table.cell>

                        <!-- État avec Toggle -->
                        <flux:table.cell class="text-center">
                            <div class="flex items-center justify-center">
                                @if($updatingMarqueId === $marque->code)
                                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                @else
                                    <button
                                        wire:click="toggleState('{{ $marque->code }}')"
                                        type="button"
                                        role="switch"
                                        aria-checked="{{ $marque->state == 1 ? 'true' : 'false' }}"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:opacity-80"
                                        style="background-color: {{ $marque->state == 1 ? '#22c55e' : '#d1d5db' }}"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            style="transform: translateX({{ $marque->state == 1 ? '24px' : '4px' }})"
                                        />
                                    </button>
                                @endif
                            </div>
                            <span class="sr-only">{{ $marque->state == 1 ? 'Actif' : 'Inactif' }}</span>
                        </flux:table.cell>

                        <!-- Actions -->
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="edit('{{ $marque->code }}')">
                                        Modifier
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $marque->code }}')">
                                        Supprimer
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon name="tag" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterState !== '')
                                        Aucune marque trouvée pour ces filtres
                                    @else
                                        Aucune marque enregistrée
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

    <livewire:pages::marques.create />
    <livewire:pages::marques.edit />
    <livewire:pages::marques.delete />
</div>
