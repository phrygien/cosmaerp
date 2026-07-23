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

    public function show(int $id): void
    {
        $this->dispatch('view-role', id: $id);
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
            ->with(['permissions' => fn($query) => $query->limit(5)])
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
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Total Rôles</p>
                <i class="hgi-stroke hgi-user-shield-01 text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Avec utilisateurs</p>
                <i class="hgi-stroke hgi-user-group text-2xl text-green-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['with_users'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Sans permission</p>
                <i class="hgi-stroke hgi-license text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['no_perm'] }}</p>
        </flux:card>
    </div>

    <!-- Barre de recherche / filtres -->
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
                    size="sm"
                >
                    <i class="hgi-stroke hgi-filter-01"></i>
                    Filtres
                </flux:button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="sortBy" class="w-full sm:w-40">
                <flux:select.option value="name">Trier par nom</flux:select.option>
                <flux:select.option value="slug">Trier par slug</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="perPage" class="w-full sm:w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>
    </div>

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

    <!-- Grille des rôles -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($this->roles as $role)
            <flux:card class="p-5 flex flex-col" wire:key="role-{{ $role->id }}">

                <div class="flex items-start justify-between">
                    <flux:heading size="lg">{{ $role->name }}</flux:heading>

                    <flux:button
                        size="sm"
                        variant="ghost"
                        inset="top bottom right"
                        wire:click="confirmDelete({{ $role->id }})"
                        title="Supprimer"
                    >
                        <i class="hgi-stroke hgi-delete-02 text-red-400"></i>
                    </flux:button>
                </div>

                <p class="text-sm text-zinc-500 mt-1">
                    Total utilisateurs avec ce rôle : {{ $role->users_count }}
                </p>

                <ul class="mt-4 space-y-2 flex-1">
                    @forelse ($role->permissions as $permission)
                        <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                            {{ $permission->name }}
                        </li>
                    @empty
                        <li class="text-sm text-zinc-400 italic">Aucune permission</li>
                    @endforelse

                    @if($role->permissions_count > $role->permissions->count())
                        <li class="flex items-center gap-2 text-sm text-zinc-400 italic">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                            et {{ $role->permissions_count - $role->permissions->count() }} de plus...
                        </li>
                    @endif
                </ul>

                <div class="flex gap-2 mt-5">
                    <flux:button variant="ghost" size="sm" wire:click="show({{ $role->id }})" class="flex-1">
                        Voir le rôle
                    </flux:button>
                    <flux:button variant="ghost" size="sm" wire:click="edit({{ $role->id }})" class="flex-1">
                        Modifier
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <div class="col-span-full">
                <flux:card class="p-5">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <i class="hgi-stroke hgi-user-shield-01 text-5xl text-zinc-400 mb-3"></i>
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
                </flux:card>
            </div>
        @endforelse

        <!-- Carte "Ajouter un rôle" -->
        <flux:modal.trigger name="create-role">
            <flux:card class="p-5 flex flex-col items-center justify-center text-center cursor-pointer border-2 border-dashed border-zinc-200 dark:border-zinc-700 hover:border-primary-400 transition min-h-[260px]">
                <i class="hgi-stroke hgi-add-square text-4xl text-zinc-400 mb-3"></i>
                <p class="text-zinc-500 font-medium">Ajouter un nouveau rôle</p>
            </flux:card>
        </flux:modal.trigger>
    </div>

    <div class="mt-6">
        <flux:pagination :paginator="$this->roles" />
    </div>

    <livewire:pages::roles.create />
    <livewire:pages::roles.edit />
    <livewire:pages::roles.delete />
    <livewire:pages::roles.view />
</div>
