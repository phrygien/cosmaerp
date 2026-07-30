<?php
use Livewire\Component;
use App\Models\Role;
use App\Models\Permission;
use App\Support\ModelFinder;
use App\Support\Permissions;
use Illuminate\Support\Str;

new class extends Component
{
    public string $name        = '';
    public string $slug        = '';
    public string $description = '';
    public string $searchModel = '';

    /**
     * Sélection courante, sous forme de clés "ability:Model"
     * ex: "view_any:User", "create:Post"
     *
     * @var array<int, string>
     */
    public array $selected = [];

    /**
     * Capacités disponibles pour chaque modèle (centralisées dans App\Support\Permissions).
     */
    public array $abilities = [];

    public function mount(): void
    {
        $this->abilities = Permissions::ABILITIES;
    }

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    protected function allModels(): array
    {
        return ModelFinder::names();
    }

    #[\Livewire\Attributes\Computed]
    public function models()
    {
        return collect($this->allModels())
            ->when($this->searchModel, fn ($collection) =>
            $collection->filter(fn ($model) =>
            str_contains(Str::lower($model), Str::lower($this->searchModel))
            )
            )
            ->values();
    }

    #[\Livewire\Attributes\Computed]
    public function selectedCount(): int
    {
        return count($this->selected);
    }

    public function isModelFullySelected(string $model): bool
    {
        $keys = collect($this->abilities)->keys()->map(fn ($a) => "{$a}:{$model}")->all();

        return count($keys) > 0
            && count(array_intersect($keys, $this->selected)) === count($keys);
    }

    public function isModelPartiallySelected(string $model): bool
    {
        $keys      = collect($this->abilities)->keys()->map(fn ($a) => "{$a}:{$model}")->all();
        $intersect = count(array_intersect($keys, $this->selected));

        return $intersect > 0 && $intersect < count($keys);
    }

    public function isAbilitySelectedForAll(string $ability): bool
    {
        $models = $this->allModels();
        $keys   = collect($models)->map(fn ($m) => "{$ability}:{$m}")->all();

        return count($keys) > 0
            && count(array_intersect($keys, $this->selected)) === count($keys);
    }

    public function toggleModel(string $model): void
    {
        $keys        = collect($this->abilities)->keys()->map(fn ($a) => "{$a}:{$model}")->all();
        $allSelected = count(array_intersect($keys, $this->selected)) === count($keys);

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $keys))
            : array_values(array_unique(array_merge($this->selected, $keys)));
    }

    public function toggleAbility(string $ability): void
    {
        $models      = $this->allModels();
        $keys        = collect($models)->map(fn ($m) => "{$ability}:{$m}")->all();
        $allSelected = count($keys) > 0 && count(array_intersect($keys, $this->selected)) === count($keys);

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $keys))
            : array_values(array_unique(array_merge($this->selected, $keys)));
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

        $permissionIds = collect($this->selected)
            ->map(function (string $key) {
                [$ability, $model] = explode(':', $key, 2);

                $permission = Permission::firstOrCreate(
                    ['slug' => Permissions::slug($ability, $model)],
                    [
                        'name'  => Permissions::name($ability, $model),
                        'group' => Permissions::group($model),
                    ]
                );

                return $permission->id;
            })
            ->all();

        $role->permissions()->sync($permissionIds);

        $this->dispatch('role-created');

        \Flux\Flux::toast(
            text: "Le rôle a été enregistré avec succès",
            variant: 'success'
        );

        $this->redirect(route('roles'), navigate: true);
    }
};
?>

<div class="max-w-4xl mx-auto">
    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('roles') }}" wire:navigate>Rôles</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Ajouter</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Ajouter un rôle</flux:heading>
            <flux:text class="mt-1">Définissez le rôle et assignez-lui des permissions.</flux:text>
        </div>
    </div>

    <div class="space-y-6">

        <!-- Informations générales -->
        <flux:card class="p-6">
            <flux:heading size="lg">Informations générales</flux:heading>
            <flux:text class="mt-1 mb-2">Définissez le nom, le slug et la description du rôle.</flux:text>

            <div class="divide-y divide-zinc-900/10 dark:divide-white/10 border-t border-zinc-900/10 dark:border-white/10">

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 py-6">
                    <flux:label class="sm:pt-2">Nom</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <flux:input
                            wire:model.live="name"
                            placeholder="Ex: Administrateur"
                            :invalid="$errors->has('name')"
                            required
                        />
                        <flux:error name="name" />
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 py-6">
                    <flux:label class="sm:pt-2">Slug</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <flux:input
                            wire:model="slug"
                            placeholder="Ex: administrateur"
                            :invalid="$errors->has('slug')"
                            required
                        />
                        <flux:error name="slug" />
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 py-6">
                    <flux:label class="sm:pt-2">Description</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <flux:textarea
                            wire:model="description"
                            placeholder="Ex: Gestion complète des utilisateurs."
                            rows="3"
                            :invalid="$errors->has('description')"
                        />
                        <flux:error name="description" />
                    </div>
                </div>

            </div>
        </flux:card>

        <!-- Permissions (matrice auto-générée depuis les modèles) -->
        <flux:card class="p-6">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Permissions</flux:heading>
                @if ($this->selectedCount > 0)
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400">
                        {{ $this->selectedCount }} sélectionnée{{ $this->selectedCount > 1 ? 's' : '' }}
                    </span>
                @endif
            </div>

            <!-- Recherche modèle -->
            <flux:input
                wire:model.live.debounce="searchModel"
                placeholder="Rechercher un modèle..."
                icon="magnifying-glass"
                class="mb-4"
            />

            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                <div class="max-h-[28rem] overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60 sticky top-0 z-10">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium">Modèle</th>
                            @foreach ($abilities as $key => $label)
                                <th class="px-3 py-2.5 text-center font-medium">
                                    <button
                                        type="button"
                                        wire:click="toggleAbility('{{ $key }}')"
                                        class="flex flex-col items-center gap-1 mx-auto"
                                    >
                                        <span>{{ $label }}</span>
                                        <flux:checkbox
                                            :checked="$this->isAbilitySelectedForAll($key)"
                                            wire:click.stop="toggleAbility('{{ $key }}')"
                                        />
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        @forelse ($this->models as $model)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40" wire:key="model-{{ $model }}">
                                <td class="px-4 py-2">
                                    <button
                                        type="button"
                                        wire:click="toggleModel('{{ $model }}')"
                                        class="flex items-center gap-2.5"
                                    >
                                            <span class="w-1.5 h-1.5 rounded-full
                                                @if($this->isModelFullySelected($model)) bg-blue-400
                                                @elseif($this->isModelPartiallySelected($model)) bg-blue-400/50
                                                @else bg-zinc-400
                                                @endif
                                            "></span>
                                        <span class="font-medium">{{ $model }}</span>
                                    </button>
                                </td>
                                @foreach ($abilities as $key => $label)
                                    <td class="px-3 py-2 text-center">
                                        <flux:checkbox
                                            wire:model="selected"
                                            value="{{ $key }}:{{ $model }}"
                                        />
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($abilities) + 1 }}" class="text-center py-8">
                                    <flux:icon name="magnifying-glass" class="text-zinc-400 mx-auto mb-2" style="width: 32px; height: 32px;" />
                                    <p class="text-sm text-zinc-400">
                                        @if ($searchModel)
                                            Aucun modèle trouvé pour "{{ $searchModel }}"
                                        @else
                                            Aucun modèle trouvé dans app/Models
                                        @endif
                                    </p>
                                    @if ($searchModel)
                                        <flux:button variant="ghost" size="sm" wire:click="$set('searchModel', '')" class="mt-2">
                                            Réinitialiser
                                        </flux:button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </flux:card>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-2">
            <flux:button
                variant="danger"
                href="{{ route('roles') }}"
                wire:navigate
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
</div>
