<?php
use Livewire\Component;
use App\Models\Marque;

new class extends Component
{
    public int    $code  = 0;
    public string $name  = '';
    public int    $state = 1;

    public function save(): void
    {
        $this->validate([
            'code'  => 'required|integer|unique:marque,code',
            'name'  => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        Marque::create([
            'code'  => $this->code,
            'name'  => $this->name,
            'state' => $this->state,
        ]);

        $this->reset(['code', 'name', 'state']);
        $this->state = 1;

        $this->dispatch('marque-created');
        $this->modal('create-marque')->close();

        \Flux\Flux::toast(
            heading: 'Création marque',
            text: "Marque créée avec succès",
            variant: 'success'
        );
    }
};
?>

<div>
    <flux:modal name="create-marque" class="md:w-96" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Ajouter une marque</flux:heading>
                <flux:text class="mt-1">Remplissez les informations de la nouvelle marque.</flux:text>
            </div>

            <!-- Code -->
            <flux:input
                wire:model="code"
                label="Code"
                type="number"
                placeholder="Ex: 1001"
                min="0"
                description="Code numérique unique de la marque."
                required
            />

            <!-- Nom -->
            <flux:input
                wire:model="name"
                label="Nom"
                placeholder="Ex: Nike"
                required
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
                    x-on:click="$flux.modal('create-marque').close()"
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
