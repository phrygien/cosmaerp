<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\ProduitFournisseur;
use Flux\Flux;

new class extends Component
{
    public ?int $produitFournisseurId = null;

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    #[On('delete-produit-fournisseur')]
    public function load(int $id): void
    {
        $this->produitFournisseurId = $id;
        Flux::modal('delete-produit-fournisseur')->show();
    }

    // ─── Computed ─────────────────────────────────────────────────────────────

    #[Computed]
    public function produit(): ?ProduitFournisseur
    {
        if (!$this->produitFournisseurId) return null;

        return ProduitFournisseur::with('product.marque', 'product.categorie')
            ->find($this->produitFournisseurId);
    }

    // ─── Delete / Cancel ──────────────────────────────────────────────────────

    public function delete(): void
    {
        $produit = ProduitFournisseur::find($this->produitFournisseurId);

        if (!$produit) {
            Flux::toast(
                heading: 'Introuvable',
                text: 'Ce produit fournisseur n\'existe plus.',
                variant: 'warning'
            );
            $this->cancel();
            return;
        }

        $designation = $produit->product?->designation ?? "Produit #{$this->produitFournisseurId}";

        $produit->delete();

        $this->dispatch('produit-fournisseur-deleted');

        Flux::toast(
            heading: 'Produit supprimé',
            text: "« {$designation} » a été dissocié du fournisseur.",
            variant: 'success'
        );

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->reset('produitFournisseurId');
        Flux::modal('delete-produit-fournisseur')->close();
    }
};
?>

<div>
    <flux:modal name="delete-produit-fournisseur" class="max-w-md w-full" :dismissible="false">
        <div class="p-6 space-y-6">

            {{-- ── Header ──────────────────────────────────────────────────── --}}
            <div class="flex items-start gap-4">
                <div>
                    <flux:heading size="lg">Supprimer l'association</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 text-sm">
                        Cette action est irréversible. Le produit sera dissocié du fournisseur.
                    </flux:text>
                </div>
            </div>

            {{-- ── Infos produit ────────────────────────────────────────────── --}}
            @if($this->produit)
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-sm truncate">
                                {{ $this->produit->product?->designation ?? '—' }}
                            </p>
                            <p class="text-xs text-zinc-400 mt-0.5">
                                {{ $this->produit->product?->product_code ?? '—' }}
                            </p>
                            @if($this->produit->product?->ref_fabri_n_1)
                                <p class="text-xs text-zinc-400 mt-0.5">
                                    Réf : {{ $this->produit->product->ref_fabri_n_1 }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-col gap-1 items-end shrink-0">
                            @if($this->produit->product?->marque)
                                <flux:badge size="sm" color="blue" inset="top bottom">
                                    {{ $this->produit->product->marque->name }}
                                </flux:badge>
                            @endif
                            @if($this->produit->product?->categorie)
                                <flux:badge size="sm" color="zinc" inset="top bottom">
                                    {{ $this->produit->product->categorie->name }}
                                </flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                        <div>
                            <p class="text-xs text-zinc-400">Prix HT</p>
                            <p class="text-sm font-medium mt-0.5">
                                {{ number_format($this->produit->prix_fournisseur_ht, 2, ',', ' ') }} €
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">Taxe</p>
                            <p class="text-sm font-medium mt-0.5">
                                {{ $this->produit->tax ?? '0' }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">État</p>
                            <div class="mt-0.5">
                                @if($this->produit->state == 1)
                                    <flux:badge size="sm" color="green" inset="top bottom">Actif</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red" inset="top bottom">Inactif</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center py-6">
                    <flux:icon name="arrow-path" class="size-6 animate-spin text-zinc-400" />
                </div>
            @endif

            {{-- ── Actions ──────────────────────────────────────────────────── --}}
            <div class="flex gap-3 pt-2">
                <flux:spacer />
                <flux:button
                    variant="ghost"
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                >
                    Annuler
                </flux:button>
                <flux:button
                    variant="danger"
                    wire:click="delete"
                    wire:loading.attr="disabled"
                    wire:confirm="Confirmer la suppression de cette association ?"
                    class="min-w-[100px]"
                >
                    <span wire:loading.remove wire:target="delete" class="flex items-center gap-2">
                        <flux:icon name="trash" class="size-4" />
                        Supprimer
                    </span>
                    <span wire:loading wire:target="delete" class="flex items-center gap-2">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        Suppression…
                    </span>
                </flux:button>
            </div>

        </div>
    </flux:modal>
</div>
