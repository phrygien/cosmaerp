<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Models\User;
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

    #[Url(as: 'statut', except: '')]
    public string $filterStatus  = '';

    #[Url(as: 'par_page', except: 10)]
    public int    $perPage       = 10;

    #[Url(as: 'supprimes', except: false)]
    public bool   $showTrashed   = false;

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

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedPerPage(): void      { $this->resetPage(); }
    public function updatedShowTrashed(): void  { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'perPage', 'showTrashed']);
        $this->resetPage();

        Flux::toast(
            heading: 'Filtres réinitialisés',
            text: 'Tous les filtres ont été réinitialisés avec succès',
            variant: 'info'
        );
    }

    #[On('user-created')]
    #[On('user-updated')]
    #[On('user-deleted')]
    #[On('user-restored')]
    public function refresh(): void
    {
        unset($this->users);
        unset($this->stats);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->dispatch('edit-user', id: $id);
    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('delete-user', id: $id);
    }

    public function restore(int $id): void
    {
        User::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('user-restored');
    }

    public function forceDelete(int $id): void
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->roles()->detach();
        $user->forceDelete();
        $this->dispatch('user-deleted');
    }

    #[Computed]
    public function stats()
    {
        return [
            'total'    => User::count(),
            'active'   => User::where('status', 'enable')->count(),
            'inactive' => User::where('status', '!=', 'enable')->count(),
            'trashed'  => User::onlyTrashed()->count(),
        ];
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        return collect([$this->filterStatus])
                ->filter(fn($v) => $v !== '')
                ->count()
            + ($this->showTrashed ? 1 : 0);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->withCount('roles')
            ->with('roles')
            ->when($this->search, fn($query) =>
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->when($this->filterStatus !== '', fn($query) =>
            $query->where('status', $this->filterStatus)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Utilisateur</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>List</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <!-- Heading + bouton -->
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Utilisateurs') }}</flux:heading>

        @if (!$showTrashed)
            <flux:modal.trigger name="create-user">
                <flux:button variant="primary" class="w-full sm:w-auto">
                    Ajouter un utilisateur
                </flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Total Utilisateurs</p>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Actifs</p>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['active'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Inactifs</p>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['inactive'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <p class="text-sm text-zinc-500">Supprimés</p>
            <p class="text-3xl font-bold mt-1 text-red-400">{{ $this->stats['trashed'] }}</p>
        </flux:card>
    </div>

    <!-- Bandeau trashed -->
    @if ($showTrashed)
        <div class="flex items-start gap-2 mb-4 px-4 py-2.5 rounded-lg bg-red-500/10 border border-red-500/20">
            <flux:icon name="exclamation-triangle" class="text-red-400 shrink-0 mt-0.5" style="width: 16px; height: 16px;" />
            <p class="text-sm text-red-400">
                Vous consultez les utilisateurs supprimés. Vous pouvez les restaurer ou les supprimer définitivement.
            </p>
        </div>
    @endif

    <flux:card class="p-5">

        <!-- En-tête tableau : recherche | toggle filtres | per page -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher un utilisateur..."
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
                        <flux:radio label="Tous"      value=""         />
                        <flux:radio label="Actifs"    value="enable"   />
                        <flux:radio label="Inactifs"  value="disable"  />
                    </flux:radio.group>

                    <!-- Toggle supprimés -->
                    <flux:tooltip :content="$showTrashed ? 'Masquer les supprimés' : 'Voir les supprimés'">
                        <flux:button
                            :variant="$showTrashed ? 'danger' : 'ghost'"
                            icon="trash"
                            size="sm"
                            wire:click="$toggle('showTrashed')"
                        >
                            {{ $showTrashed ? 'Masquer les supprimés' : 'Voir les supprimés' }}
                        </flux:button>
                    </flux:tooltip>
                </div>
            </div>
        @endif

        <!-- Table -->
        <flux:table :paginate="$this->users" variant="bordered">
            <flux:table.columns>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Utilisateur
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'email'"
                    :direction="$sortDirection"
                    wire:click="sort('email')"
                    class="hidden md:table-cell"
                >
                    Email
                </flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'status'"
                    :direction="$sortDirection"
                    wire:click="sort('status')"
                >
                    Statut
                </flux:table.column>

                <flux:table.column class="hidden lg:table-cell">Rôles</flux:table.column>

                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                    class="hidden sm:table-cell"
                >
                    Créé le
                </flux:table.column>

                @if ($showTrashed)
                    <flux:table.column class="hidden sm:table-cell">Supprimé le</flux:table.column>
                @endif

                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row :key="$user->id" wire:key="user-{{ $user->id }}" class="{{ $showTrashed ? 'opacity-60' : '' }}">

                        <!-- Utilisateur -->
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" name="{{ $user->name }}" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $user->name }}</p>
                                    <!-- Email visible en mobile uniquement -->
                                    <p class="text-xs text-zinc-400 truncate md:hidden">{{ $user->email }}</p>
                                    <!-- Date visible en mobile uniquement -->
                                    <p class="text-xs text-zinc-400 sm:hidden">
                                        {{ $user->created_at->translatedFormat('d F Y') }}
                                    </p>
                                </div>
                            </div>
                        </flux:table.cell>

                        <!-- Email cachée en mobile -->
                        <flux:table.cell class="hidden md:table-cell text-zinc-400 text-sm">
                            {{ $user->email }}
                        </flux:table.cell>

                        <!-- Statut -->
                        <flux:table.cell>
                            @if ($user->status === 'enable')
                                <flux:badge size="sm" color="green" inset="top bottom">Activé</flux:badge>
                            @else
                                <flux:badge size="sm" color="red" inset="top bottom">Désactivé</flux:badge>
                            @endif
                        </flux:table.cell>

                        <!-- Rôles cachés en mobile/tablet -->
                        <flux:table.cell class="hidden lg:table-cell">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    <flux:badge size="sm" color="purple" inset="top bottom">
                                        {{ $role->name }}
                                    </flux:badge>
                                @empty
                                    <span class="text-zinc-400 text-sm">Aucun rôle</span>
                                @endforelse
                            </div>
                        </flux:table.cell>

                        <!-- Créé le caché en mobile -->
                        <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm whitespace-nowrap">
                            {{ $user->created_at->translatedFormat('d F Y') }}
                        </flux:table.cell>

                        <!-- Supprimé le caché en mobile -->
                        @if ($showTrashed)
                            <flux:table.cell class="hidden sm:table-cell text-red-400 text-sm whitespace-nowrap">
                                {{ $user->deleted_at->translatedFormat('d F Y') }}
                            </flux:table.cell>
                        @endif

                        <!-- Actions -->
                        <flux:table.cell>
                            @if ($showTrashed)
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                    <flux:menu>
                                        <flux:menu.item icon="arrow-uturn-left" wire:click="restore({{ $user->id }})">
                                            Restaurer
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="forceDelete({{ $user->id }})">
                                            Supprimer définitivement
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @else
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil" wire:click="edit({{ $user->id }})">
                                            Modifier
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $user->id }})">
                                            Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$showTrashed ? 7 : 6">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:icon
                                    :name="$showTrashed ? 'trash' : 'users'"
                                    class="text-zinc-400 mb-3"
                                    style="width: 40px; height: 40px;"
                                />
                                <p class="text-zinc-400 font-medium text-sm">
                                    @if ($search || $filterStatus !== '' || $showTrashed)
                                        Aucun utilisateur trouvé pour ces filtres
                                    @else
                                        Aucun utilisateur enregistré
                                    @endif
                                </p>
                                @if ($search || $filterStatus !== '')
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

    <livewire:pages::users.create />
    <livewire:pages::users.edit />
    <livewire:pages::users.delete />
</div>
