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

    #[Url(as: 'enligne', except: '')]
    public string $filterOnline  = '';

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

    public function updatedSearch(): void        { $this->resetPage(); }
    public function updatedPerPage(): void       { $this->resetPage(); }
    public function updatedShowTrashed(): void   { $this->resetPage(); }
    public function updatedFilterStatus(): void  { $this->resetPage(); }
    public function updatedFilterOnline(): void  { $this->resetPage(); }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterOnline', 'perPage', 'showTrashed']);
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
        return collect([$this->filterStatus, $this->filterOnline])
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
            ->when($this->filterOnline === 'online', fn($q) =>
            $q->where('last_seen_at', '>=', now()->subMinutes(5))
            )
            ->when($this->filterOnline === 'offline', fn($q) =>
            $q->where(fn($q) =>
            $q->whereNull('last_seen_at')
                ->orWhere('last_seen_at', '<', now()->subMinutes(5))
            )
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

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Total Utilisateurs</p>
                <i class="hgi-stroke hgi-user-group text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Actifs</p>
                <i class="hgi-stroke hgi-checkmark-circle-01 text-2xl text-green-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-green-500">{{ $this->stats['active'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Inactifs</p>
                <i class="hgi-stroke hgi-cancel-circle text-2xl text-zinc-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-zinc-400">{{ $this->stats['inactive'] }}</p>
        </flux:card>

        <flux:card class="p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-zinc-500">Supprimés</p>
                <i class="hgi-stroke hgi-delete-02 text-2xl text-red-400"></i>
            </div>
            <p class="text-3xl font-bold mt-1 text-red-400">{{ $this->stats['trashed'] }}</p>
        </flux:card>
    </div>

    {{-- Bandeau trashed --}}
    @if ($showTrashed)
        <div class="flex items-start gap-2 mb-4 px-4 py-2.5 rounded-lg bg-red-500/10 border border-red-500/20">
            <i class="hgi-stroke hgi-alert-02 text-red-400 shrink-0 mt-0.5"></i>
            <p class="text-sm text-red-400">
                Vous consultez les utilisateurs supprimés. Vous pouvez les restaurer ou les supprimer définitivement.
            </p>
        </div>
    @endif

    {{-- Barre de recherche / filtres --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
        <div class="flex items-center gap-2">
            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher un utilisateur..."
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

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="sortBy" class="w-full sm:w-40">
                <flux:select.option value="name">Trier par nom</flux:select.option>
                <flux:select.option value="email">Trier par email</flux:select.option>
                <flux:select.option value="created_at">Trier par date</flux:select.option>
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
                @if($this->activeFiltersCount > 0)
                    <flux:button wire:click="resetFilters" variant="ghost" size="xs" class="text-red-500 hover:text-red-600">
                        Réinitialiser
                    </flux:button>
                @endif
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">

                {{-- Filtre statut compte --}}
                <flux:radio.group wire:model.live="filterStatus" variant="segmented">
                    <flux:radio label="Tous"     value=""        />
                    <flux:radio label="Actifs"   value="enable"  />
                    <flux:radio label="Inactifs" value="disable" />
                </flux:radio.group>

                {{-- Filtre présence en ligne --}}
                <flux:radio.group wire:model.live="filterOnline" variant="segmented">
                    <flux:radio label="Tous"       value=""        />
                    <flux:radio label="En ligne"   value="online"  />
                    <flux:radio label="Hors ligne" value="offline" />
                </flux:radio.group>

                {{-- Voir supprimés --}}
                <flux:tooltip :content="$showTrashed ? 'Masquer les supprimés' : 'Voir les supprimés'">
                    <flux:button
                        :variant="$showTrashed ? 'danger' : 'ghost'"
                        size="sm"
                        wire:click="$toggle('showTrashed')"
                    >
                        <i class="hgi-stroke hgi-delete-02"></i>
                        {{ $showTrashed ? 'Masquer les supprimés' : 'Voir les supprimés' }}
                    </flux:button>
                </flux:tooltip>

            </div>
        </div>
    @endif

    {{-- Grille des utilisateurs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($this->users as $user)
            <flux:card class="p-5 flex flex-col {{ $showTrashed ? 'opacity-60' : '' }}" wire:key="user-{{ $user->id }}">

                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative shrink-0">
                            <flux:avatar
                                size="lg"
                                src="https://unavatar.io/{{ $user->email }}"
                                name="{{ $user->name }}"
                                color="{{ $user->is_online ? 'teal' : ($user->last_seen_at ? 'yellow' : 'rose') }}"
                            />
                            <span
                                title="{{ $user->is_online
                                    ? 'En ligne'
                                    : ($user->last_seen_at
                                        ? 'Vu ' . $user->last_seen_at->diffForHumans()
                                        : 'Jamais connecté') }}"
                                class="absolute -bottom-0.5 -right-0.5 block w-3 h-3 rounded-full ring-2 ring-white dark:ring-zinc-900
                                       {{ $user->is_online ? 'bg-green-400' : ($user->last_seen_at ? 'bg-yellow-400' : 'bg-rose-400') }}"
                            ></span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-sm truncate">{{ $user->name }}</p>
                            <p class="text-xs text-zinc-400 truncate">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>

                <p class="text-[11px] font-medium mt-2
                    {{ $user->is_online ? 'text-green-500' : ($user->last_seen_at ? 'text-yellow-500' : 'text-rose-400') }}">
                    @if ($user->is_online)
                        En ligne
                    @elseif ($user->last_seen_at)
                        Vu {{ $user->last_seen_at->diffForHumans() }}
                    @else
                        Jamais connecté
                    @endif
                </p>

                <div class="flex items-center gap-2 mt-3">
                    @if ($user->status === 'enable')
                        <flux:badge size="sm" color="green" inset="top bottom">Activé</flux:badge>
                    @else
                        <flux:badge size="sm" color="red" inset="top bottom">Désactivé</flux:badge>
                    @endif

                    <span class="text-xs text-zinc-400">
                        Créé le {{ $user->created_at->translatedFormat('d F Y') }}
                    </span>
                </div>

                @if ($showTrashed)
                    <p class="text-xs text-red-400 mt-1">
                        Supprimé le {{ $user->deleted_at->translatedFormat('d F Y') }}
                    </p>
                @endif

                <div class="mt-3 flex-1">
                    <p class="text-xs text-zinc-500 mb-1.5">Rôles</p>
                    <div class="flex flex-wrap gap-1">
                        @forelse ($user->roles as $role)
                            <flux:badge size="sm" color="purple" inset="top bottom">{{ $role->name }}</flux:badge>
                        @empty
                            <span class="text-zinc-400 text-sm">Aucun rôle</span>
                        @endforelse
                    </div>
                </div>

                <div class="flex gap-2 mt-5 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    @if ($showTrashed)
                        <flux:button variant="ghost" size="sm" wire:click="restore({{ $user->id }})" class="flex-1">
                            <i class="hgi-stroke hgi-arrow-turn-backward text-green-400"></i>
                            Restaurer
                        </flux:button>

                        <flux:button variant="ghost" size="sm" wire:click="forceDelete({{ $user->id }})" class="flex-1">
                            <i class="hgi-stroke hgi-delete-02 text-red-400"></i>
                            Supprimer
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="sm" wire:click="edit({{ $user->id }})" class="flex-1">
                            Modifier
                        </flux:button>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            inset="top bottom right"
                            wire:click="confirmDelete({{ $user->id }})"
                            title="Supprimer"
                        >
                            <i class="hgi-stroke hgi-delete-02 text-red-400"></i>
                        </flux:button>
                    @endif
                </div>
            </flux:card>
        @empty
            <div class="col-span-full">
                <flux:card class="p-5">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        @if ($showTrashed)
                            <i class="hgi-stroke hgi-delete-02 text-5xl text-zinc-400 mb-3"></i>
                        @else
                            <i class="hgi-stroke hgi-user-group text-5xl text-zinc-400 mb-3"></i>
                        @endif
                        <p class="text-zinc-400 font-medium text-sm">
                            @if ($search || $filterStatus !== '' || $filterOnline !== '' || $showTrashed)
                                Aucun utilisateur trouvé pour ces filtres
                            @else
                                Aucun utilisateur enregistré
                            @endif
                        </p>
                        @if ($search || $filterStatus !== '' || $filterOnline !== '')
                            <flux:button variant="ghost" size="sm" wire:click="resetFilters" class="mt-3">
                                Réinitialiser les filtres
                            </flux:button>
                        @endif
                    </div>
                </flux:card>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        <flux:pagination :paginator="$this->users" />
    </div>

    <livewire:pages::users.create />
    <livewire:pages::users.edit />
    <livewire:pages::users.delete />
</div>
