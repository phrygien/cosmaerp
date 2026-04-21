<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Magasin;
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

    #[Url(as: 'type', except: '')]
    public string $filterType    = '';

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    public bool  $showFilters    = false;
    public array $updatingStates = [];

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

    #[On('magasin-created')]
    #[On('magasin-updated')]
    #[On('magasin-deleted')]
    public function refresh(): void
    {
        unset($this->magasins);
        unset($this->stats);
        $this->resetPage();
    }

    public function toggleState(int $id): void
    {
        $this->updatingStates[$id] = true;

        try {
            DB::beginTransaction();

            $magasin  = Magasin::findOrFail($id);
            $newState = $magasin->state == 1 ? 0 : 1;

            $magasin->state = $newState;
            $magasin->save();

            DB::commit();

            unset($this->magasins);
            unset($this->stats);

            $this->dispatch('magasin-updated');

            Flux::toast(
                heading: $newState == 1 ? 'Magasin activé' : 'Magasin désactivé',
                text: "Le magasin \"{$magasin->name}\" a été " . ($newState == 1 ? 'activé' : 'désactivé') . ' avec succès',
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: "Impossible de modifier l'état du magasin : " . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            unset($this->updatingStates[$id]);
        }
    }

    public function edit(int $id): void
    {
        $this->dispatch('edit-magasin', id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-magasin', id: $id);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total'    => Magasin::count(),
            'active'   => Magasin::where('state', 1)->count(),
            'inactive' => Magasin::where('state', 0)->count(),
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
    public function types()
    {
        return Magasin::query()
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');
    }

    #[Computed]
    public function magasins()
    {
        return Magasin::query()
            ->when($this->search, fn($q) =>
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('telephone', 'like', "%{$this->search}%")
                ->orWhere('adress', 'like', "%{$this->search}%")
            )
            ->when($this->filterState !== '', fn($q) =>
            $q->where('state', $this->filterState)
            )
            ->when($this->filterType !== '', fn($q) =>
            $q->where('type', $this->filterType)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Depot</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Liste</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Heading + bouton -->
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Depot') }}</flux:heading>

        <flux:modal.trigger name="create-magasin">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter un depot
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Total Depot</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Depot Actifs</p>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['active'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Depot Inactifs</p>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['inactive'] }}</p>
        </flux:card>
    </div>

    <flux:card class="p-5">

        <!-- En-tête tableau : recherche | toggle filtres | per page -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher un depot..."
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
                    <flux:radio.group wire:model.live="filterState" variant="segmented">
                        <flux:radio label="Tous"    value=""  />
                        <flux:radio label="Actif"   value="1" />
                        <flux:radio label="Inactif" value="0" />
                    </flux:radio.group>

                    @if ($this->types->isNotEmpty())
                        <flux:select wire:model.live="filterType" class="w-full sm:w-48">
                            <flux:select.option value="">Tous les types</flux:select.option>
                            @foreach ($this->types as $type)
                                <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
            </div>
        @endif

        <!-- Table -->
        <flux:table :paginate="$this->magasins" variant="bordered">
            <flux:table.columns>
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
                    :sorted="$sortBy === 'type'"
                    :direction="$sortDirection"
                    wire:click="sort('type')"
                    class="hidden sm:table-cell"
                >
                    Type
                </flux:table.column>

                <flux:table.column class="hidden md:table-cell">
                    Contact
                </flux:table.column>

                <flux:table.column class="hidden lg:table-cell">
                    URL
                </flux:table.column>

                <flux:table.column class="text-center">
                    État
                </flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->magasins as $magasin)
                    <flux:table.row :key="$magasin->id" wire:key="magasin-{{ $magasin->id }}">

                        <!-- Nom -->
                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $magasin->name }}</p>
                            <!-- Type visible en mobile -->
                            @if ($magasin->type)
                                <p class="mt-0.5 sm:hidden">
                                    <flux:badge size="sm" color="purple" inset="top bottom">
                                        {{ $magasin->type }}
                                    </flux:badge>
                                </p>
                            @endif
                            <!-- Contact visible en mobile -->
                            <div class="text-xs text-zinc-400 mt-0.5 md:hidden space-y-0.5">
                                @if ($magasin->email)
                                    <p>{{ $magasin->email }}</p>
                                @endif
                                @if ($magasin->telephone)
                                    <p>{{ $magasin->telephone }}</p>
                                @endif
                            </div>
                            @if ($magasin->adress)
                                <p class="text-xs text-zinc-400 mt-0.5 md:hidden">{{ $magasin->adress }}</p>
                            @endif
                        </flux:table.cell>

                        <!-- Type caché en mobile -->
                        <flux:table.cell class="hidden sm:table-cell">
                            @if ($magasin->type)
                                <flux:badge size="sm" color="purple" inset="top bottom">
                                    {{ $magasin->type }}
                                </flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <!-- Contact caché en mobile -->
                        <flux:table.cell class="hidden md:table-cell">
                            <div class="space-y-0.5">
                                @if ($magasin->email)
                                    <p class="text-xs text-zinc-400">{{ $magasin->email }}</p>
                                @endif
                                @if ($magasin->telephone)
                                    <p class="text-xs text-zinc-400">{{ $magasin->telephone }}</p>
                                @endif
                                @if (!$magasin->email && !$magasin->telephone)
                                    <span class="text-zinc-400 text-sm">—</span>
                                @endif
                            </div>
                        </flux:table.cell>

                        <!-- URL cachée en mobile/tablet -->
                        <flux:table.cell class="hidden lg:table-cell">
                            @if ($magasin->store_url)
                                <a href="{{ $magasin->store_url }}"
                                   target="_blank"
                                   class="text-xs text-blue-400 hover:text-blue-300 hover:underline truncate max-w-xs block">
                                    {{ $magasin->store_url }}
                                </a>
                            @else
                                <span class="text-zinc-400 text-sm">—</span>
                            @endif
                        </flux:table.cell>

                        <!-- État avec Toggle -->
                        <flux:table.cell class="text-center">
                            <div class="flex items-center justify-center">
                                @if(isset($updatingStates[$magasin->id]))
                                    <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                @else
                                    <button
                                        wire:click="toggleState({{ $magasin->id }})"
                                        type="button"
                                        role="switch"
                                        aria-checked="{{ $magasin->state == 1 ? 'true' : 'false' }}"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hover:opacity-80"
                                        style="background-color: {{ $magasin->state == 1 ? '#22c55e' : '#d1d5db' }}"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            style="transform: translateX({{ $magasin->state == 1 ? '24px' : '4px' }})"
                                        />
                                    </button>
                                @endif
                            </div>
                            <span class="sr-only">{{ $magasin->state == 1 ? 'Actif' : 'Inactif' }}</span>
                        </flux:table.cell>

                        <!-- Actions -->
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="edit({{ $magasin->id }})">
                                        Modifier
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $magasin->id }})">
                                        Supprimer
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon name="building-storefront" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterState !== '' || $filterType !== '')
                                        Aucun magasin trouvé pour ces filtres
                                    @else
                                        Aucun magasin enregistré
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

    <livewire:pages::magasin.create />
    <livewire:pages::magasin.edit />
    <livewire:pages::magasin.delete />
</div>
