<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\ProduitFournisseur;
use Flux\Flux;

new class extends Component
{
    public ?int $produitFournisseurId = null;

    public float  $prixHT = 0;
    public float  $tax    = 0;
    public int    $state  = 1;

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    #[On('edit-produit-fournisseur')]
    public function load(int $id): void
    {
        $produit = ProduitFournisseur::with('product.marque', 'product.categorie')
            ->findOrFail($id);

        $this->produitFournisseurId = $produit->id;
        $this->prixHT               = $produit->prix_fournisseur_ht;
        $this->tax                  = $produit->tax;
        $this->state                = $produit->state;

        Flux::modal('edit-produit-fournisseur')->show();
    }

    // ─── Computed ─────────────────────────────────────────────────────────────

    #[Computed]
    public function produit(): ?ProduitFournisseur
    {
        if (!$this->produitFournisseurId) return null;

        return ProduitFournisseur::with('product.marque', 'product.categorie')
            ->find($this->produitFournisseurId);
    }

    // ─── Save / Cancel ────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'prixHT' => ['required', 'numeric', 'min:0'],
            'tax'    => ['required', 'numeric', 'min:0', 'max:100'],
            'state'  => ['required', 'in:0,1'],
        ], [
            'prixHT.required' => 'Le prix HT est requis.',
            'prixHT.numeric'  => 'Le prix HT doit être un nombre.',
            'prixHT.min'      => 'Le prix HT doit être positif.',
            'tax.required'    => 'La taxe est requise.',
            'tax.numeric'     => 'La taxe doit être un nombre.',
            'tax.min'         => 'La taxe doit être entre 0 et 100.',
            'tax.max'         => 'La taxe doit être entre 0 et 100.',
            'state.required'  => "L'état est requis.",
            'state.in'        => "L'état doit être Actif ou Inactif.",
        ]);

        $produit = ProduitFournisseur::findOrFail($this->produitFournisseurId);

        $produit->update([
            'prix_fournisseur_ht' => $this->prixHT,
            'tax'                 => $this->tax,
            'state'               => $this->state,
        ]);

        $this->dispatch('produit-fournisseur-updated');

        Flux::toast(
            heading: 'Produit mis à jour',
            text: 'Les informations ont été enregistrées avec succès.',
            variant: 'success'
        );

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->reset(['produitFournisseurId', 'prixHT', 'tax', 'state']);
        $this->state = 1;
        Flux::modal('edit-produit-fournisseur')->close();
    }
};
?>

<div>
    <flux:modal name="edit-produit-fournisseur" class="max-w-lg w-full" :dismissible="false">
        <div class="p-6 space-y-6">

            {{-- ── Header ──────────────────────────────────────────────────── --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4">
                <flux:heading size="lg">Modifier le produit fournisseur</flux:heading>
                <flux:text class="mt-1 text-zinc-500">
                    Modifiez le prix, la taxe ou l'état de l'association.
                </flux:text>
            </div>

            @if($this->produit)

                {{-- ── Infos produit (lecture seule) ───────────────────────── --}}
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-sm">
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
                            @if($this->produit->product?->EAN)
                                <p class="text-xs text-zinc-400 mt-0.5">
                                    EAN : {{ $this->produit->product->EAN }}
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
                </div>

                {{-- ── Formulaire ───────────────────────────────────────────── --}}
                <div class="space-y-4">

                    {{-- Prix HT --}}
                    <flux:field>
                        <flux:label>Prix fournisseur HT</flux:label>
                        <flux:input
                            wire:model="prixHT"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            suffix="€"
                        />
                        <flux:error name="prixHT" />
                    </flux:field>

                    {{-- Taxe --}}
                    <flux:field>
                        <flux:label>Taxe</flux:label>
                        <flux:input
                            wire:model="tax"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            placeholder="0.00"
                            suffix="%"
                        />
                        <flux:error name="tax" />
                    </flux:field>

                    {{-- État --}}
                    <flux:field>
                        <flux:label>État</flux:label>
                        <flux:select wire:model="state">
                            <flux:select.option value="1">Actif</flux:select.option>
                            <flux:select.option value="0">Inactif</flux:select.option>
                        </flux:select>
                        <flux:error name="state" />
                    </flux:field>

                </div>

            @else
                <div class="flex items-center justify-center py-8">
                    <flux:icon name="arrow-path" class="size-6 animate-spin text-zinc-400" />
                </div>
            @endif

            {{-- ── Actions ─────────────────────────────────────────────────── --}}
            <div class="flex gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:spacer />
                <flux:button
                    variant="ghost"
                    wire:click="cancel"
                    wire:loading.attr="disabled"
                >
                    Annuler
                </flux:button>
                <flux:button
                    variant="primary"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="min-w-[100px]"
                >
                    <span wire:loading.remove wire:target="save">Enregistrer</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <flux:icon name="arrow-path" class="size-4 animate-spin" />
                        Enregistrement…
                    </span>
                </flux:button>
            </div>

        </div>
    </flux:modal>
</div>
