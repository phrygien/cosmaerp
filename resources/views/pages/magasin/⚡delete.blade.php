<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Magasin;
use Flux\Flux;

new class extends Component
{
    public ?int    $magasinId   = null;
    public ?string $magasinName = null;

    #[On('delete-magasin')]
    public function loadMagasin(int $id): void
    {
        $magasin = Magasin::findOrFail($id);

        $this->magasinId   = $magasin->id;
        $this->magasinName = $magasin->name;

        $this->modal('delete-magasin')->show();
    }

    public function delete(): void
    {
        try {
            Magasin::findOrFail($this->magasinId)->delete();

            $this->modal('delete-magasin')->close();
            $this->reset(['magasinId', 'magasinName']);
            $this->dispatch('magasin-deleted');

            Flux::toast(
                heading: 'Magasin supprimé',
                text: 'Le magasin a été supprimé avec succès.',
                variant: 'success'
            );

        } catch (\Illuminate\Database\QueryException $e) {
            $this->modal('delete-magasin')->close();
            $this->reset(['magasinId', 'magasinName']);

            if ($e->getCode() === '23000') {
                Flux::toast(
                    heading: 'Suppression impossible',
                    text: 'Ce magasin est lié à des commandes existantes et ne peut pas être supprimé.',
                    variant: 'danger'
                );
            } else {
                Flux::toast(
                    heading: 'Erreur',
                    text: 'Une erreur est survenue lors de la suppression.',
                    variant: 'danger'
                );
            }
        }
    }
};
?>

<div>
    <flux:modal name="delete-magasin" class="min-w-[22rem]" :dismissible="false">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">Supprimer le magasin ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer le magasin
                    <strong>{{ $magasinName }}</strong>.<br>
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
