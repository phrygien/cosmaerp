<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Category;
use App\Models\Marque;

new class extends Component
{
    public int    $code         = 0;
    public int    $originalCode = 0;
    public string $name         = '';
    public string $marqueCode   = '';
    public int    $state        = 1;

    #[On('edit-category')]
    public function loadCategory(int $code): void
    {
        $category = Category::where('code', $code)->firstOrFail();

        $this->code         = $category->code;
        $this->originalCode = $category->code;
        $this->name         = $category->name;
        $this->marqueCode   = (string) $category->marque_code;
        $this->state        = $category->state;

        $this->modal('edit-category')->show();
    }

    public function update(): void
    {
        $this->validate([
            'code'       => 'required|integer|unique:categorie,code,' . $this->originalCode . ',code',
            'name'       => 'required|string|max:255',
            'marqueCode' => 'required|exists:marque,code',
            'state'      => 'required|boolean',
        ]);

        Category::where('code', $this->originalCode)->firstOrFail()->update([
            'code'        => $this->code,
            'name'        => $this->name,
            'marque_code' => $this->marqueCode,
            'state'       => $this->state,
        ]);

        $this->reset(['code', 'originalCode', 'name', 'marqueCode', 'state']);

        $this->dispatch('category-updated');
        $this->modal('edit-category')->close();

        \Flux\Flux::toast(
            heading: 'Mise à jour de la catégorie',
            text: 'Catégorie mise à jour avec succès',
            variant: 'success'
        );
    }

    #[Computed]
    public function marques()
    {
        return Marque::active()->orderBy('name')->get();
    }
};
?>

<div>
    <flux:modal name="edit-category" class="md:w-96 overflow-visible!" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Modifier la catégorie</flux:heading>
                <flux:text class="mt-1">Modifiez les informations de la catégorie.</flux:text>
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
                    x-on:click="$flux.modal('edit-category').close()"
                >
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
</div>
