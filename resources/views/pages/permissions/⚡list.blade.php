<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Permission;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public string $sortBy = "name";
    public string $sortDirection = "asc";
    public string $search = "";
    public int $perPage = 10;

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection =
                $this->sortDirection === "asc" ? "desc" : "asc";
        } else {
            $this->sortBy = $column;
            $this->sortDirection = "asc";
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[On("permission-created")]
    public function refreshPermissions(): void
    {
        unset($this->permissions); // reset le computed
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->dispatch("edit-permission", id: $id);
    }

    #[On("permission-updated")]
    public function refreshOnUpdated(): void
    {
        unset($this->permissions);
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->with("roles")
            ->when(
                $this->search,
                fn($query) => $query
                    ->where("name", "like", "%{$this->search}%")
                    ->orWhere("group", "like", "%{$this->search}%"),
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }
};
?>

<div class="mt-5">

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div>
                <flux:input
                    wire:model.live.debounce="search"
                    placeholder="Rechercher une permission..."
                    icon="magnifying-glass"
                    class="w-82"
                />
            </div>

            <flux:select wire:model.live="perPage" class="w-20">
                <flux:select.option value="5">5</flux:select.option>
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="25">25</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
        </div>

        <flux:modal.trigger name="create-permission">
            <flux:button variant="primary">
                Ajouter une permission
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:table :paginate="$this->permissions">
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
            >
                Slug
            </flux:table.column>

            <flux:table.column
                sortable
                :sorted="$sortBy === 'group'"
                :direction="$sortDirection"
                wire:click="sort('group')"
            >
                Groupe
            </flux:table.column>

            <flux:table.column>Rôles</flux:table.column>

            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->permissions as $permission)
                <flux:table.row :key="$permission->id">

                    <flux:table.cell variant="strong">
                        {{ $permission->name }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $permission->slug }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $permission->group ?? '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($permission->roles as $role)
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $role->name }}
                                </flux:badge>
                            @empty
                                <span class="text-zinc-400 text-sm">Aucun rôle</span>
                            @endforelse
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" wire:click="edit({{ $permission->id }})">
                                    Modifier
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger">
                                    Supprimer
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>

                </flux:table.row>

            @empty
                <!-- Empty state -->
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="shield-exclamation" class="text-zinc-400 mb-3" style="width: 40px; height: 40px;" />
                            <p class="text-zinc-400 font-medium text-sm">
                                @if ($search)
                                    Aucune permission trouvée pour "{{ $search }}"
                                @else
                                    Aucune permission enregistrée
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

    <livewire:pages::permissions.create />
    <livewire:pages::permissions.edit />
</div>
