<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\Role;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'tri')]
    public string $sortBy        = 'name';

    #[Url(as: 'ordre')]
    public string $sortDirection = 'asc';

    #[Url(as: 'q', except: '')]
    public string $search        = '';

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

    public function updatedSearch(): void  { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'perPage']);
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    #[On('role-created')]
    #[On('role-updated')]
    #[On('role-deleted')]
    public function refresh(): void
    {
        unset($this->roles);
        unset($this->stats);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->dispatch('edit-role', id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-role', id: $id);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total'       => Role::count(),
            'with_users'  => Role::has('users')->count(),
            'no_perm'     => Role::doesntHave('permissions')->count(),
        ];
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($this->search, fn($query) =>
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Rôles</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Liste</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Heading + bouton -->
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Rôles') }}</flux:heading>

        <flux:modal.trigger name="create-role">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter un rôle
            </flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Total Rôles</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Avec utilisateurs</p>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['with_users'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Sans permission</p>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['no_perm'] }}</p>
        </flux:card>
    </div>

    <flux:card class="p-5">

        <!-- En-tête tableau : recherche | toggle filtres | per page -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher un rôle..."
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
                    <flux:button wire:click="resetFilters" variant="ghost" size="xs" class="text-red-500 hover:text-red-600">
                        Réinitialiser
                    </flux:button>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
                    <p class="text-sm text-zinc-400 italic">Aucun filtre avancé disponible pour les rôles.</p>
                </div>
            </div>
        @endif

        <!-- Table -->
        <flux:table :paginate="$this->roles" variant="bordered">
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
                    :sorted="$sortBy === 'slug'"
                    :direction="$sortDirection"
                    wire:click="sort('slug')"
                    class="hidden sm:table-cell"
                >
                    Slug
                </flux:table.column>

                <flux:table.column class="hidden lg:table-cell">
                    Description
                </flux:table.column>

                <flux:table.column class="hidden md:table-cell">
                    Permissions
                </flux:table.column>

                <flux:table.column class="hidden md:table-cell">
                    Utilisateurs
                </flux:table.column>

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->roles as $role)
                    <flux:table.row :key="$role->id" wire:key="role-{{ $role->id }}">

                        <!-- Nom -->
                        <flux:table.cell>
                            <p class="font-medium text-sm">{{ $role->name }}</p>
                            <!-- Slug visible en mobile uniquement -->
                            <p class="text-xs mt-0.5 sm:hidden">
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $role->slug }}
                                </flux:badge>
                            </p>
                            <!-- Permissions + Utilisateurs visibles en mobile/tablet uniquement -->
                            <div class="flex gap-1 mt-1 md:hidden">
                                <flux:badge size="sm" color="purple" inset="top bottom">
                                    {{ $role->permissions_count }} perm.
                                </flux:badge>
                                <flux:badge size="sm" color="green" inset="top bottom">
                                    {{ $role->users_count }} util.
                                </flux:badge>
                            </div>
                        </flux:table.cell>

                        <!-- Slug caché en mobile -->
                        <flux:table.cell class="hidden sm:table-cell">
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $role->slug }}
                            </flux:badge>
                        </flux:table.cell>

                        <!-- Description cachée en mobile/tablet -->
                        <flux:table.cell class="hidden lg:table-cell text-zinc-400">
                            {{ $role->description ?? '—' }}
                        </flux:table.cell>

                        <!-- Permissions cachées en mobile -->
                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge size="sm" color="purple" inset="top bottom">
                                {{ $role->permissions_count }} permission{{ $role->permissions_count > 1 ? 's' : '' }}
                            </flux:badge>
                        </flux:table.cell>

                        <!-- Utilisateurs cachés en mobile -->
                        <flux:table.cell class="hidden md:table-cell">
                            <flux:badge size="sm" color="green" inset="top bottom">
                                {{ $role->users_count }} utilisateur{{ $role->users_count > 1 ? 's' : '' }}
                            </flux:badge>
                        </flux:table.cell>

                        <!-- Actions -->
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="edit({{ $role->id }})">
                                        Modifier
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $role->id }})">
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
                                <flux:icon name="user-group" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search)
                                        Aucun rôle trouvé pour ces filtres
                                    @else
                                        Aucun rôle enregistré
                                    @endif
                                </p>
                                @if ($search)
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

    <livewire:pages::roles.create />
    <livewire:pages::roles.edit />
    <livewire:pages::roles.delete />
</div>
