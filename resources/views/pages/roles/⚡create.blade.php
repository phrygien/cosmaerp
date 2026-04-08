<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Str;

new class extends Component
{
    public string $name              = '';
    public string $slug              = '';
    public string $description       = '';
    public string $searchPermission  = '';
    public array  $selectedPermissions = [];

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function toggleAll(string $group): void
    {
        $ids = Permission::where('group', $group)->pluck('id')->toArray();
        $allSelected = count(array_intersect($ids, $this->selectedPermissions)) === count($ids);

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $ids));
        } else {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $ids)));
        }
    }

    public function isGroupFullySelected(string $group): bool
    {
        $ids = Permission::where('group', $group)->pluck('id')->toArray();
        return count($ids) > 0 &&
            count(array_intersect($ids, $this->selectedPermissions)) === count($ids);
    }

    public function isGroupPartiallySelected(string $group): bool
    {
        $ids = Permission::where('group', $group)->pluck('id')->toArray();
        $intersect = count(array_intersect($ids, $this->selectedPermissions));
        return $intersect > 0 && $intersect < count($ids);
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:500',
        ]);

        $role = Role::create([
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
        ]);

        $role->permissions()->sync($this->selectedPermissions);

        $this->reset(['name', 'slug', 'description', 'selectedPermissions', 'searchPermission']);

        $this->dispatch('role-created');
        $this->modal('create-role')->close();

        \Flux\Flux::toast(
            heading: 'Rôle créé',
            text: "Le rôle a été enregistré avec succès",
            variant: 'success'
        );
    }

    #[Computed]
    public function groupedPermissions()
    {
        return Permission::query()
            ->when($this->searchPermission, fn($query) =>
            $query->where('name', 'like', "%{$this->searchPermission}%")
                ->orWhere('slug', 'like', "%{$this->searchPermission}%")
            )
            ->get()
            ->groupBy('group');
    }
};
?>

<div>
    <flux:modal name="create-role" class="md:w-2xl" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Ajouter un rôle</flux:heading>
                <flux:text class="mt-1">Définissez le rôle et assignez-lui des permissions.</flux:text>
            </div>

            <!-- Nom + Slug -->
            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    wire:model.live="name"
                    label="Nom"
                    placeholder="Ex: Administrateur"
                    required
                />
                <flux:input
                    wire:model="slug"
                    label="Slug"
                    placeholder="Ex: administrateur"
                    required
                />
            </div>

            <!-- Description -->
            <flux:textarea
                wire:model="description"
                label="Description"
                placeholder="Ex: Gestion complète des utilisateurs."
                rows="2"
            />

            <!-- Permissions -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <flux:label>Permissions</flux:label>
                    @if (count($selectedPermissions) > 0)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400">
                            {{ count($selectedPermissions) }} sélectionnée{{ count($selectedPermissions) > 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>

                <!-- Recherche permission -->
                <flux:input
                    wire:model.live.debounce="searchPermission"
                    placeholder="Rechercher une permission..."
                    icon="magnifying-glass"
                    class="mb-3"
                />

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse ($this->groupedPermissions as $group => $permissions)
                        <flux:card class="p-0 overflow-hidden">

                            <!-- Group header -->
                            <button
                                type="button"
                                wire:click="toggleAll('{{ $group }}')"
                                class="w-full flex items-center justify-between px-3 py-2.5 hover:bg-zinc-100 dark:hover:bg-zinc-700/80 transition-colors duration-150"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full
                                        @if($this->isGroupFullySelected($group)) bg-blue-400
                                        @elseif($this->isGroupPartiallySelected($group)) bg-blue-400/50
                                        @else bg-zinc-400
                                        @endif
                                    "></span>
                                    <span class="text-sm font-medium">{{ $group }}</span>
                                    <span class="text-xs text-zinc-500">{{ $permissions->count() }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($this->isGroupFullySelected($group))
                                        <span class="text-xs text-blue-400">Tout décocher</span>
                                    @elseif($this->isGroupPartiallySelected($group))
                                        <span class="text-xs text-zinc-400">Partiel</span>
                                    @else
                                        <span class="text-xs text-zinc-400">Tout cocher</span>
                                    @endif
                                    <flux:checkbox
                                        :checked="$this->isGroupFullySelected($group)"
                                        wire:click.stop="toggleAll('{{ $group }}')"
                                    />
                                </div>
                            </button>

                            <!-- Permissions list -->
                            <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                                @foreach ($permissions as $permission)
                                    <label class="flex items-center justify-between px-4 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/60 cursor-pointer transition-colors duration-100 gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm truncate">{{ $permission->name }}</p>
                                            <p class="text-xs text-zinc-500 font-mono">{{ $permission->slug }}</p>
                                        </div>
                                        <flux:checkbox
                                            wire:model="selectedPermissions"
                                            value="{{ $permission->id }}"
                                        />
                                    </label>
                                @endforeach
                            </div>

                        </flux:card>

                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="magnifying-glass" class="text-zinc-400 mb-2" style="width: 32px; height: 32px;" />
                            <p class="text-sm text-zinc-400">
                                Aucune permission trouvée pour "{{ $searchPermission }}"
                            </p>
                            <flux:button variant="ghost" size="sm" wire:click="$set('searchPermission', '')" class="mt-2">
                                Réinitialiser
                            </flux:button>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-1">
                <flux:spacer />
                <flux:button
                    variant="ghost"
                    x-on:click="$flux.modal('create-role').close()"
                >
                    Annuler
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="save"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Créer le rôle</span>
                    <span wire:loading wire:target="save">Création...</span>
                </flux:button>
            </div>

        </div>
    </flux:modal>
</div>
