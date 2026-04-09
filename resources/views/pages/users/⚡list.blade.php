<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\User;

new class extends Component
{
    use WithPagination;

    public string $sortBy        = 'name';
    public string $sortDirection = 'asc';
    public string $search        = '';
    public int    $perPage       = 10;
    public bool   $showTrashed   = false;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }
    public function updatedShowTrashed(): void { $this->resetPage(); }

    #[On('user-created')]
    #[On('user-updated')]
    #[On('user-deleted')]
    #[On('user-restored')]
    public function refresh(): void
    {
        unset($this->users);
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
    public function users()
    {
        return User::query()
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->withCount('roles')
            ->when($this->search, fn($query) =>
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher un utilisateur..."
                icon="magnifying-glass"
                style="width: 350px;"
            />

            <flux:select wire:model.live="perPage" class="w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>

            <!-- Toggle trashed -->
            <flux:tooltip :content="$showTrashed ? 'Masquer les supprimés' : 'Voir les supprimés'">
                <flux:button
                    :variant="$showTrashed ? 'primary' : 'ghost'"
                    icon="trash"
                    wire:click="$toggle('showTrashed')"
                />
            </flux:tooltip>
        </div>

        @if (!$showTrashed)
            <flux:modal.trigger name="create-user">
                <flux:button variant="primary">
                    Ajouter un utilisateur
                </flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    <!-- Bandeau trashed -->
    @if ($showTrashed)
        <div class="flex items-center gap-2 mb-4 px-4 py-2.5 rounded-lg bg-red-500/10 border border-red-500/20">
            <flux:icon name="exclamation-triangle" class="text-red-400" style="width: 16px; height: 16px;" />
            <p class="text-sm text-red-400">
                Vous consultez les utilisateurs supprimés. Vous pouvez les restaurer ou les supprimer définitivement.
            </p>
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

            <flux:table.column>Rôles</flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'created_at'"
                :direction="$sortDirection"
                wire:click="sort('created_at')"
            >
                Créé le
            </flux:table.column>

            @if ($showTrashed)
                <flux:table.column>Supprimé le</flux:table.column>
            @endif

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row :key="$user->id" class="{{ $showTrashed ? 'opacity-60' : '' }}">

                    <!-- Utilisateur -->
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" name="{{ $user->name }}" />
                            <div>
                                <p class="text-sm font-medium">{{ $user->name }}</p>
                            </div>
                        </div>
                    </flux:table.cell>

                    <!-- Email -->
                    <flux:table.cell class="text-zinc-400 text-sm">
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

                    <!-- Rôles -->
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-2 py-3">
                            @forelse ($user->roles as $role)
                                <flux:badge size="sm" color="purple" inset="top bottom" class="mb-2">
                                    {{ $role->name }}
                                </flux:badge>
                            @empty
                                <span class="text-zinc-400 text-sm">Aucun rôle</span>
                            @endforelse
                        </div>
                    </flux:table.cell>

                    <!-- Créé le -->
                    <flux:table.cell class="text-zinc-400 text-sm whitespace-nowrap">
                        {{ $user->created_at->translatedFormat('d F Y') }}
                    </flux:table.cell>

                    <!-- Supprimé le -->
                    @if ($showTrashed)
                        <flux:table.cell class="text-red-400 text-sm whitespace-nowrap">
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
                                    <flux:menu.item wire:click="edit({{ $user->id }})">
                                        Modifier
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item variant="danger" wire:click="confirmDelete({{ $user->id }})">
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
                                @if ($search)
                                    Aucun utilisateur trouvé pour "{{ $search }}"
                                @elseif ($showTrashed)
                                    Aucun utilisateur supprimé
                                @else
                                    Aucun utilisateur enregistré
                                @endif
                            </p>
                            @if ($search)
                                <flux:button variant="ghost" size="sm" wire:click="$set('search', '')" class="mt-3">
                                    Réinitialiser la recherche
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:pages::users.create />
    <livewire:pages::users.edit />
    <livewire:pages::users.delete />
</div>
