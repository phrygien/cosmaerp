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
        <flux:breadcrumbs.item href="#">Utilisateur</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>List</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">{{ __('Utilisateurs') }}</flux:heading>

        @if (!$showTrashed)
            <flux:modal.trigger name="create-user">
                <flux:button variant="primary" class="w-full sm:w-auto">
                    <i class="hgi-stroke hgi-add-circle"></i>
                    Ajouter un utilisateur
                </flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
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

    <!-- Bandeau trashed -->
    @if ($showTrashed)
        <div class="flex items-start gap-2 mb-4 px-4 py-2.5 rounded-lg bg-red-500/10 border border-red-500/20">
            <i class="hgi-stroke hgi-alert-02 text-red-400 shrink-0 mt-0.5"></i>
            <p class="text-sm text-red-400">
                Vous consultez les utilisateurs supprimés. Vous pouvez les restaurer ou les supprimer définitivement.
            </p>
        </div>
    @endif

    <flux:card class="p-5">

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
                    <flux:radio.group wire:model.live="filterStatus" variant="segmented">
                        <flux:radio label="Tous"     value=""        />
                        <flux:radio label="Actifs"   value="enable"  />
                        <flux:radio label="Inactifs" value="disable" />
                    </flux:radio.group>

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

        <flux:table :paginate="$this->users" variant="bordered">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Utilisateur</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')" class="hidden md:table-cell">Email</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Statut</flux:table.column>
                <flux:table.column class="hidden lg:table-cell">Rôles</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')" class="hidden sm:table-cell">Créé le</flux:table.column>
                @if ($showTrashed)
                    <flux:table.column class="hidden sm:table-cell">Supprimé le</flux:table.column>
                @endif
                <flux:table.column class="text-right">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row :key="$user->id" wire:key="user-{{ $user->id }}" class="{{ $showTrashed ? 'opacity-60' : '' }}">

                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" name="{{ $user->name }}" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-zinc-400 truncate md:hidden">{{ $user->email }}</p>
                                    <p class="text-xs text-zinc-400 sm:hidden">{{ $user->created_at->translatedFormat('d F Y') }}</p>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden md:table-cell text-zinc-400 text-sm">
                            {{ $user->email }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @if ($user->status === 'enable')
                                <flux:badge size="sm" color="green" inset="top bottom">Activé</flux:badge>
                            @else
                                <flux:badge size="sm" color="red" inset="top bottom">Désactivé</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="hidden lg:table-cell">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    <flux:badge size="sm" color="purple" inset="top bottom">{{ $role->name }}</flux:badge>
                                @empty
                                    <span class="text-zinc-400 text-sm">Aucun rôle</span>
                                @endforelse
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="hidden sm:table-cell text-zinc-400 text-sm whitespace-nowrap">
                            {{ $user->created_at->translatedFormat('d F Y') }}
                        </flux:table.cell>

                        @if ($showTrashed)
                            <flux:table.cell class="hidden sm:table-cell text-red-400 text-sm whitespace-nowrap">
                                {{ $user->deleted_at->translatedFormat('d F Y') }}
                            </flux:table.cell>
                        @endif

                        {{-- Actions directes --}}
                        <flux:table.cell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if ($showTrashed)
                                    {{-- Restaurer --}}
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        inset="top bottom"
                                        wire:click="restore({{ $user->id }})"
                                        title="Restaurer"
                                    >
                                        <i class="hgi-stroke hgi-arrow-turn-backward text-green-400"></i>
                                    </flux:button>

                                    {{-- Supprimer définitivement --}}
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        inset="top bottom"
                                        wire:click="forceDelete({{ $user->id }})"
                                        title="Supprimer définitivement"
                                    >
                                        <i class="hgi-stroke hgi-delete-02 text-red-400"></i>
                                    </flux:button>
                                @else
                                    {{-- Modifier --}}
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        inset="top bottom"
                                        wire:click="edit({{ $user->id }})"
                                        title="Modifier"
                                    >
                                        <i class="hgi-stroke hgi-pencil-edit-01 text-indigo-400"></i>
                                    </flux:button>

                                    {{-- Supprimer --}}
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        inset="top bottom"
                                        wire:click="confirmDelete({{ $user->id }})"
                                        title="Supprimer"
                                    >
                                        <i class="hgi-stroke hgi-delete-02 text-red-400"></i>
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>

                    </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$showTrashed ? 7 : 6">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                @if ($showTrashed)
                                    <i class="hgi-stroke hgi-delete-02 text-5xl text-zinc-400 mb-3"></i>
                                @else
                                    <i class="hgi-stroke hgi-user-group text-5xl text-zinc-400 mb-3"></i>
                                @endif
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
