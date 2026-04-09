<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Category;
use App\Models\Marque;

new class extends Component
{
    public int    $code        = 0;
    public string $name        = '';
    public string $marqueCode  = '';
    public int    $state       = 1;

    public function save(): void
    {
        $this->validate([
            'code'       => 'required|integer|unique:categorie,code',
            'name'       => 'required|string|max:255',
            'marqueCode' => 'required|exists:marque,code',
            'state'      => 'required|boolean',
        ]);

        Category::create([
            'code'        => $this->code,
            'name'        => $this->name,
            'marque_code' => $this->marqueCode,
            'state'       => $this->state,
        ]);

        $this->reset(['code', 'name', 'marqueCode', 'state']);
        $this->state = 1;

        $this->dispatch('category-created');
        $this->modal('create-category')->close();
    }

    #[Computed]
    public function marques()
    {
        return Marque::active()->orderBy('name')->get();
    }
};
?>

<div>
    <flux:modal name="create-category" class="md:w-96 overflow-visible!" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Ajouter une catégorie</flux:heading>
                <flux:text class="mt-1">Remplissez les informations de la nouvelle catégorie.</flux:text>
            </div>

            <!-- Code -->
            <flux:input
                wire:model="code"
                label="Code"
                type="number"
                placeholder="Ex: 1001"
                min="0"
                description="Code numérique unique de la catégorie."
                required
            />

            <!-- Nom -->
            <flux:input
                wire:model="name"
                label="Nom"
                placeholder="Ex: Chaussures"
                required
            />

            <!-- Marque -->
            <x-searchable-select
                label="Marque"
                model="marqueCode"
                :options="$this->marques"
                option-value="code"
                option-label="name"
                placeholder="Sélectionner une marque..."
                search-placeholder="Rechercher une marque..."
                empty-message="Aucune marque trouvée"
            />

            <!-- État -->
            <flux:radio.group
                wire:model="state"
                label="État"
                variant="segmented"
                size="sm"
            >
                <flux:radio label="Actif" value="1" />
                <flux:radio label="Inactif" value="0" />
            </flux:radio.group>

            <!-- Actions -->
            <div class="flex gap-2 pt-1">
                <flux:spacer />
                <flux:button
                    variant="ghost"
                    x-on:click="$flux.modal('create-category').close()"
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
</div>
