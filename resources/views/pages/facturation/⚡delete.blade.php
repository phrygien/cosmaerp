<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Facture;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public ?int    $factureId     = null;
    public ?string $factureNumero = null;

    #[On('delete-facture')]
    public function open(int $id): void
    {
        $facture = Facture::find($id);

        if (! $facture) {
            Flux::toast(
                heading: 'Introuvable',
                text: 'La facture est introuvable.',
                variant: 'danger'
            );
            return;
        }

        if ($facture->state == 1) {
            Flux::toast(
                heading: 'Action impossible',
                text: 'Une facture validée ne peut pas être supprimée.',
                variant: 'warning'
            );
            return;
        }

        $this->factureId     = $facture->id;
        $this->factureNumero = $facture->numero;

        Flux::modal('delete-facture')->show();
    }

    public function delete(): void
    {
        if (! $this->factureId) return;

        try {
            DB::beginTransaction();

            $facture = Facture::findOrFail($this->factureId);

            if ($facture->state == 1) {
                Flux::toast(
                    heading: 'Action impossible',
                    text: 'Une facture validée ne peut pas être supprimée.',
                    variant: 'warning'
                );
                Flux::modal('delete-facture')->close();
                return;
            }

            $facture->detailsFacture()->delete();
            $facture->delete();

            DB::commit();

            $this->dispatch('facture-deleted');

            Flux::toast(
                heading: 'Facture supprimée',
                text: "La facture \"{$this->factureNumero}\" a été supprimée avec succès.",
                variant: 'success'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: 'Impossible de supprimer la facture : ' . $e->getMessage(),
                variant: 'danger'
            );
        } finally {
            $this->factureId     = null;
            $this->factureNumero = null;
            Flux::modal('delete-facture')->close();
        }
    }
};
?>

<div>
    <flux:modal name="delete-facture" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Supprimer la facture ?</flux:heading>
                <flux:text class="mt-2">
                    Vous êtes sur le point de supprimer la facture
                    <strong>{{ $factureNumero }}</strong>.<br>
                    Cette action est irréversible et supprimera également toutes les lignes associées.
                </flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">
                    Supprimer
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
