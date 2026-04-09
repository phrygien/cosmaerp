<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Fournisseur;

new class extends Component
{
    public ?int    $fournisseurId   = null;
    public ?string $fournisseurName = null;

    #[On('delete-fournisseur')]
    public function loadFournisseur(int $id): void
    {
        $fournisseur = Fournisseur::findOrFail($id);

        $this->fournisseurId   = $fournisseur->id;
        $this->fournisseurName = $fournisseur->name;

        $this->modal('delete-fournisseur')->show();
    }

    public function delete(): void
    {
        Fournisseur::findOrFail($this->fournisseurId)->delete();

        $this->reset(['fournisseurId', 'fournisseurName']);

        $this->dispatch('fournisseur-deleted');
        $this->modal('delete-fournisseur')->close();
    }
};
?>

<div>
    <flux:modal name="delete-fournisseur" class="min-w-[22rem]" :dismissible="false">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Supprimer le fournisseur ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer le fournisseur
                    <strong>{{ $fournisseurName }}</strong>.<br>
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
