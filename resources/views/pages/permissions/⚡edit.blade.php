<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Permission;
use Illuminate\Support\Str;

new class extends Component {
    public ?int $permissionId = null;
    public string $name = "";
    public string $slug = "";
    public string $group = "";

    #[On("edit-permission")]
    public function loadPermission(int $id): void
    {
        $permission = Permission::findOrFail($id);

        $this->permissionId = $permission->id;
        $this->name = $permission->name;
        $this->slug = $permission->slug;
        $this->group = $permission->group ?? "";

        $this->modal("edit-permission")->show();
    }

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function update(): void
    {
        $this->validate([
            "name" => "required|string|max:255",
            "slug" =>
                "required|string|max:255|unique:permissions,slug," .
                $this->permissionId,
            "group" => "nullable|string|max:255",
        ]);

        Permission::findOrFail($this->permissionId)->update([
            "name" => $this->name,
            "slug" => $this->slug,
            "group" => $this->group,
        ]);

        $this->reset(["permissionId", "name", "slug", "group"]);

        $this->dispatch("permission-updated");
        $this->modal("edit-permission")->close();
    }
};
?>

<flux:modal name="edit-permission" class="md:w-96">
    <div class="space-y-6">

        <div>
            <flux:heading size="lg">Modifier la permission</flux:heading>
            <flux:text class="mt-2">Modifiez les informations de la permission.</flux:text>
        </div>

        <flux:input
            wire:model.live="name"
            label="Nom"
            placeholder="Ex: Voir les utilisateurs"
            required
        />

        <flux:input
            wire:model="slug"
            label="Slug"
            placeholder="Ex: users.index"
            description="Généré automatiquement depuis le nom."
            required
        />

        <flux:input
            wire:model="group"
            label="Groupe"
            placeholder="Ex: Utilisateurs"
        />

        <div class="flex gap-2">
            <flux:spacer />
            <flux:button variant="ghost" x-on:click="$flux.modal('edit-permission').close()">
                Annuler
            </flux:button>
            <flux:button
                variant="primary"
                wire:click="update"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="update">Enregistrer</span>
                <span wire:loading wire:target="update">Enregistrement...</span>
            </flux:button>
        </div>

    </div>
</flux:modal>
