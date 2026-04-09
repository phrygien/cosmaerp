<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Marque;

new class extends Component
{
    public ?int    $marqueCode = null;
    public ?string $marqueName = null;

    #[On('delete-marque')]
    public function loadMarque(string $code): void
    {
        $marque = Marque::findOrFail($code);

        $this->marqueCode = $marque->code;
        $this->marqueName = $marque->name;

        $this->modal('delete-marque')->show();
    }

    public function delete(): void
    {
        $marque = Marque::findOrFail($this->marqueCode);
        $marque->delete();

        $this->reset(['marqueCode', 'marqueName']);

        $this->dispatch('marque-deleted');
        $this->modal('delete-marque')->close();

        \Flux\Flux::toast(
            heading: 'Suppression de la marque',
            text: "Marque supprimée avec succès",
            variant: 'success'
        );
    }
};
?>

<div>
    <flux:modal name="delete-marque" class="min-w-[22rem]" :dismissible="false">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Supprimer la marque ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer la marque
                    <strong>{{ $marqueName }}</strong>.<br>
                    Cette action est irréversible.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="danger"
                    wire:click="delete"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="delete">Supprimer</span>
                    <span wire:loading wire:target="delete">Suppression...</span>
                </flux:button>
            </div>

        </div>
    </flux:modal>
</div>
