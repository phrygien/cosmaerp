<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Permission;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy        = "name";

    #[Url(as: 'ordre')]
    public string $sortDirection = "asc";

    #[Url(as: 'q', except: '')]
    public string $search        = "";

    #[Url(as: 'groupe', except: '')]
    public string $filterGroup   = "";

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    public bool $showFilters = false;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === "asc" ? "desc" : "asc";
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = "asc";
        }
    }

    public function updatedSearch(): void      { $this->resetPage(); }
    public function updatedPerPage(): void     { $this->resetPage(); }
    public function updatedFilterGroup(): void { $this->resetPage(); }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterGroup', 'perPage']);
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    #[On("permission-created")]
    #[On("permission-updated")]
    #[On('permission-deleted')]
    public function refresh(): void
    {
        unset($this->permissions);
        unset($this->stats);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->dispatch("edit-permission", id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-permission', id: $id);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total'  => Permission::count(),
            'groups' => Permission::whereNotNull('group')->distinct('group')->count('group'),
            'no_role' => Permission::doesntHave('roles')->count(),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->filterGroup])
            ->filter(fn($v) => $v !== '')
            ->count();
    }

    #[Computed]
    public function groups()
    {
        return Permission::whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->with("roles")
            ->when($this->search, fn($query) =>
            $query->where("name", "like", "%{$this->search}%")
                ->orWhere("group", "like", "%{$this->search}%")
                ->orWhere("slug", "like", "%{$this->search}%")
            )
            ->when($this->filterGroup, fn($query) =>
            $query->where("group", $this->filterGroup)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Permissions</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Liste</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Permissions') }}</flux:heading>

        <flux:modal.trigger name="create-permission">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter une permission
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Total Permissions</p>
                <i class="hgi-stroke hgi-license text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Groupes distincts</p>
                <i class="hgi-stroke hgi-folder-01 text-2xl text-blue-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-blue-500">{{ $this->stats['groups'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Sans rôle assigné</p>
                <i class="hgi-stroke hgi-alert-02 text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['no_role'] }}</p>
        </flux:card>
    </div>

    <flux:card class="p-5">

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher une permission..."
                    icon="magnifying-glass"
                    class="w-full sm:w-80"
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
                    <flux:select wire:model.live="filterGroup" class="w-full sm:w-56">
                        <flux:select.option value="">Tous les groupes</flux:select.option>
                        @foreach ($this->groups as $group)
                            <flux:select.option value="{{ $group }}">{{ $group }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        @endif

        <flux:table :paginate="$this->permissions" variant="bordered">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Nom</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'slug'" :direction="$sortDirection" wire:click="sort('slug')" class="hidden sm:table-cell">Slug</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'group'" :direction="$sortDirection" wire:click="sort('group')" class="hidden md:table-cell">Groupe</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">Rôles</flux:table.column>
                <flux:table.column class="text-right">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->permissions as $permission)
                    <flux:table.row :key="$permission->id" wire:key="permission-{{ $permission->id }}">

                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $permission->name }}</p>
                            <p class="mt-0.5 sm:hidden">
                                <flux:badge size="sm" color="zinc" inset="top bottom">{{ $permission->slug }}</flux:badge>
                            </p>
                            <p class="text-xs text-zinc-400 mt-0.5 md:hidden">{{ $permission->group ?? '—' }}</p>
                            <div class="flex flex-wrap gap-1 mt-1 lg:hidden">
                                @forelse ($permission->roles as $role)
                                    <flux:badge size="sm" color="blue" inset="top bottom">{{ $role->name }}</flux:badge>
                                @empty
                                    <span class="text-zinc-400 text-xs">Aucun rôle</span>
                                @endforelse
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell whitespace-nowrap">
                            <flux:badge size="sm" color="zinc" inset="top bottom">{{ $permission->slug }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell text-zinc-400">
                            {{ $permission->group ?? '—' }}
                        </flux:table.cell>

                        <flux:table.cell class="hidden lg:table-cell">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($permission->roles as $role)
                                    <flux:badge size="sm" color="blue" inset="top bottom">{{ $role->name }}</flux:badge>
                                @empty
                                    <span class="text-zinc-400 text-sm">Aucun rôle</span>
                                @endforelse
                            </div>
                        </flux:table.cell>

                        {{-- Actions directes --}}
                        <flux:table.cell class="text-right">
                            <div class="flex items-center justify-end gap-1">

                                {{-- Modifier --}}
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    inset="top bottom"
                                    wire:click="edit({{ $permission->id }})"
                                    title="Modifier"
                                >
                                    <i class="hgi-stroke hgi-pencil-edit-01 text-indigo-400"></i>
                                </flux:button>

                                {{-- Supprimer --}}
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    inset="top bottom"
                                    wire:click="confirmDelete({{ $permission->id }})"
                                    title="Supprimer"
                                >
                                    <i class="hgi-stroke hgi-delete-02 text-red-400"></i>
                                </flux:button>

                            </div>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <i class="hgi-stroke hgi-license text-5xl text-zinc-400 mb-3"></i>
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterGroup)
                                        Aucune permission trouvée pour ces filtres
                                    @else
                                        Aucune permission enregistrée
                                    @endif
                                </p>
                                @if ($search || $filterGroup)
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

    <livewire:pages::permissions.create />
    <livewire:pages::permissions.edit />
    <livewire:pages::permissions.delete />
</div>
