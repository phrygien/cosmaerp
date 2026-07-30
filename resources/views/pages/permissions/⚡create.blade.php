<?php
use Livewire\Component;
use App\Models\Permission;
use Illuminate\Support\Str;

new class extends Component {
    public string $name = "";
    public string $slug = "";
    public string $group = "";

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        $this->validate([
            "name" => "required|string|max:255",
            "slug" => "required|string|max:255|unique:permissions,slug",
            "group" => "nullable|string|max:255",
        ]);

        Permission::create([
            "name" => $this->name,
            "slug" => $this->slug,
            "group" => $this->group,
        ]);

        $this->reset(["name", "slug", "group"]);

        $this->dispatch("permission-created");
        $this->dispatch("close-modal", name: "create-permission");

        \Flux\Flux::toast(
            text: "Permissions créées avec succès",
            variant: 'success'
        );
    }
};
?>

<flux:modal name="create-permission" flyout>
    <form wire:submit="save" class="space-y-6">

        <div>
            <flux:heading size="lg">Ajouter une permission</flux:heading>
            <flux:text class="mt-2">Remplissez les informations de la nouvelle permission.</flux:text>
        </div>

        <flux:input
            label="Nom"
            wire:model.live="name"
            placeholder="Ex: Voir les utilisateurs"
            required
        />

        <flux:input
            label="Slug"
            wire:model="slug"
            placeholder="Ex: users.index"
            description="Généré automatiquement depuis le nom."
            required
        />

        <flux:input
            label="Groupe"
            wire:model="group"
            placeholder="Ex: Utilisateurs"
        />

        <div class="flex">
            <flux:spacer />
            <flux:button
                type="button"
                variant="ghost"
                x-on:click="$flux.modal('create-permission').close()"
            >
                Annuler
            </flux:button>
            <flux:button
                type="submit"
                variant="primary"
                wire:loading.attr="disabled"
                wire:target="save"
                class="ml-2"
            >
                <span wire:loading.remove wire:target="save">Créer</span>
                <span wire:loading wire:target="save">Création...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
