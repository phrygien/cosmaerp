<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Role;

new class extends Component
{
    use WithPagination;

    public string $sortBy        = 'name';
    public string $sortDirection = 'asc';
    public string $search        = '';
    public int    $perPage       = 10;

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

    #[On('role-created')]
    #[On('role-updated')]
    #[On('role-deleted')]
    public function refresh(): void
    {
        unset($this->roles);
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
    <!-- Header -->
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <flux:input
                wire:model.live.debounce="search"
                placeholder="Rechercher un rôle..."
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

        <flux:modal.trigger name="create-role">
            <flux:button variant="primary" class="w-full sm:w-auto">
                Ajouter un rôle
            </flux:button>
        </flux:modal.trigger>
    </div>

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
                <flux:table.row :key="$role->id">

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
                                    Aucun rôle trouvé pour "{{ $search }}"
                                @else
                                    Aucun rôle enregistré
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

    <livewire:pages::roles.create />
    <livewire:pages::roles.edit />
    <livewire:pages::roles.delete />
</div>
