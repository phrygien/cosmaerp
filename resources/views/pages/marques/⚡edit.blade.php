<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Marque;

new class extends Component
{
    public int    $code  = 0;
    public string $originalCode = '';
    public string $name  = '';
    public int    $state = 1;

    #[On('edit-marque')]
    public function loadMarque(string $code): void
    {
        $marque = Marque::findOrFail($code);

        $this->code         = $marque->code;
        $this->originalCode = $marque->code;
        $this->name         = $marque->name;
        $this->state        = $marque->state;

        $this->modal('edit-marque')->show();
    }

    public function update(): void
    {
        $this->validate([
            'code'  => 'required|integer|unique:marque,code,' . $this->originalCode . ',code',
            'name'  => 'required|string|max:255',
            'state' => 'required|boolean',
        ]);

        Marque::findOrFail($this->originalCode)->update([
            'code'  => $this->code,
            'name'  => $this->name,
            'state' => $this->state,
        ]);

        $this->reset(['code', 'originalCode', 'name', 'state']);

        $this->dispatch('marque-updated');
        $this->modal('edit-marque')->close();

        \Flux\Flux::toast(
            heading: 'Mise à jour de la marque',
            text: "Marque mise à jour avec succès",
            variant: 'success'
        );
    }
};
?>

<div>
    <flux:modal name="edit-marque" class="md:w-96" :dismissible="false">
        <div class="space-y-5">

            <!-- Header -->
            <div>
                <flux:heading size="lg">Modifier la marque</flux:heading>
                <flux:text class="mt-1">Modifiez les informations de la marque.</flux:text>
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
                    x-on:click="$flux.modal('edit-marque').close()"
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
