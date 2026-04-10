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

<flux:modal name="create-permission" focusable class="max-w-lg">
    <div class="space-y-6">

        <!-- Header -->
        <div>
            <flux:heading size="lg">Ajouter une permission</flux:heading>
            <flux:text class="mt-2">Remplissez les informations de la nouvelle permission.</flux:text>
        </div>

        <!-- Nom -->
        <flux:input
            wire:model.live="name"
            label="Nom"
            placeholder="Ex: Voir les utilisateurs"
            required
        />

        <!-- Slug (auto-généré) -->
        <flux:input
            wire:model="slug"
            label="Slug"
            placeholder="Ex: users.index"
            description="Généré automatiquement depuis le nom."
            required
        />

        <!-- Groupe -->
        <flux:input
            wire:model="group"
            label="Groupe"
            placeholder="Ex: Utilisateurs"
        />

        <!-- Actions -->
        <div class="flex gap-2">
            <flux:spacer />
            <flux:button
                variant="ghost"
                x-on:click="$flux.modal('create-permission').close()"
            >
                Annuler
            </flux:button>
            <flux:button
                variant="primary"
                wire:click="save"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="save">Créer</span>
                <span wire:loading wire:target="save">Création...</span>
            </flux:button>
        </div>

    </div>
</flux:modal>
