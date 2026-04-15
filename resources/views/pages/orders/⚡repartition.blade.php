<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Magasin;
use App\Models\Product;
use App\Models\DetailCommande;
use App\Models\DestinationDetailCommande;

new class extends Component
{
    public int  $commande_id;
    public int  $product_id;
    public ?int $detail_id  = null;   // null = création, int = modification
    public bool $edit_mode  = false;

    public float $pu_achat_ht  = 0.0;
    public float $taux_remise  = 0.0;
    public float $tax          = 0.0;
    public float $pu_achat_net = 0.0;

    /** @var array<int, int> magasin_id => quantite */
    public array $repartitions = [];

    public function mount(): void
    {
        // Initialise toutes les lignes à 0
        foreach ($this->magasins as $magasin) {
            $this->repartitions[$magasin->id] = 0;
        }

        if ($this->edit_mode && $this->detail_id) {
            // ── Mode édition : pré-charger les valeurs existantes ──────────
            $detail = DetailCommande::with('destinations')->find($this->detail_id);

            if ($detail) {
                $this->pu_achat_ht  = (float) $detail->pu_achat_HT;
                $this->taux_remise  = (float) $detail->taux_remise;
                $this->tax          = (float) $detail->tax;
                $this->pu_achat_net = (float) $detail->pu_achat_net;

                foreach ($detail->destinations as $dest) {
                    $this->repartitions[(int) $dest->magasin_id] = (int) $dest->quantite;
                }
            }
        } else {
            // ── Mode création : TVA par défaut depuis le produit ───────────
            $this->tax = (float) ($this->product?->tva ?? 0);
        }
    }

    #[Computed]
    public function product()
    {
        return Product::with(['marque', 'categorie'])->find($this->product_id);
    }

    #[Computed]
    public function magasins()
    {
        return Magasin::where('state', 1)->orderBy('name')->get();
    }

    #[Computed]
    public function totalQuantite(): int
    {
        return (int) array_sum(array_map('intval', $this->repartitions));
    }

    public function updatedPuAchatHt($value): void
    {
        $this->pu_achat_ht = $value === '' || $value === null ? 0 : (float) $value;
        $this->computeNet();
    }

    public function updatedTauxRemise($value): void
    {
        $this->taux_remise = $value === '' || $value === null ? 0 : (float) $value;
        $this->computeNet();
    }

    private function computeNet(): void
    {
        $this->pu_achat_net = round(
            $this->pu_achat_ht * (1 - $this->taux_remise / 100),
            4
        );
    }

    protected function rules(): array
    {
        $rules = [
            'pu_achat_ht'  => 'required|numeric|min:0',
            'taux_remise'  => 'required|numeric|min:0|max:100',
            'tax'          => 'required|numeric|min:0',
            'repartitions' => 'required|array',
        ];

        foreach ($this->repartitions as $magasinId => $_) {
            $rules["repartitions.{$magasinId}"] = 'integer|min:0';
        }

        return $rules;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->totalQuantite === 0) {
            $this->addError('repartitions', 'Veuillez saisir au moins une quantité.');
            return;
        }

        $this->computeNet();

        if ($this->edit_mode && $this->detail_id) {
            $this->update();
        } else {
            $this->create();
        }

        $this->dispatch('repartition-saved');
    }

    private function create(): void
    {
        $detail = DetailCommande::create([
            'commande_id'  => $this->commande_id,
            'product_id'   => $this->product_id,
            'pu_achat_HT'  => $this->pu_achat_ht,
            'tax'          => $this->tax,
            'taux_remise'  => $this->taux_remise,
            'pu_achat_net' => $this->pu_achat_net,
            'quantite'     => $this->totalQuantite,
            'state'        => 1,
        ]);

        foreach ($this->repartitions as $magasinId => $quantite) {
            if ((int) $quantite > 0) {
                DestinationDetailCommande::create([
                    'detail_commande_id' => $detail->id,
                    'magasin_id'         => $magasinId,
                    'quantite'           => (int) $quantite,
                    'state'              => 1,
                ]);
            }
        }
    }

    private function update(): void
    {
        $detail = DetailCommande::findOrFail($this->detail_id);

        // Vérification de sécurité : le détail appartient bien à cette commande
        abort_if($detail->commande_id !== $this->commande_id, 403);

        $detail->update([
            'pu_achat_HT'  => $this->pu_achat_ht,
            'tax'          => $this->tax,
            'taux_remise'  => $this->taux_remise,
            'pu_achat_net' => $this->pu_achat_net,
            'quantite'     => $this->totalQuantite,
        ]);

        // Supprimer les anciennes destinations puis recréer
        // (plus simple et fiable qu'un updateOrCreate sur chaque ligne)
        $detail->destinations()->delete();

        foreach ($this->repartitions as $magasinId => $quantite) {
            if ((int) $quantite > 0) {
                DestinationDetailCommande::create([
                    'detail_commande_id' => $detail->id,
                    'magasin_id'         => $magasinId,
                    'quantite'           => (int) $quantite,
                    'state'              => 1,
                ]);
            }
        }
    }
};
?>

<div class="space-y-6 p-1">

    {{-- En-tête produit --}}
    <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 p-4">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-xs text-indigo-400 font-mono mb-1">{{ $this->product?->product_code }}</p>
                <p class="font-semibold text-indigo-900 dark:text-indigo-100 text-base">
                    {{ $this->product?->designation }}
                </p>
                @if($this->product?->designation_variant)
                    <p class="text-sm text-indigo-600 dark:text-indigo-300 mt-0.5">
                        {{ $this->product->designation_variant }}
                    </p>
                @endif
            </div>

            {{-- Badge mode édition / ajout --}}
            @if($edit_mode)
                <flux:badge color="amber" icon="pencil-square">Modification</flux:badge>
            @else
                <flux:badge color="green" icon="plus">Nouvel ajout</flux:badge>
            @endif
        </div>

        <div class="flex gap-2 mt-2 flex-wrap">
            @if($this->product?->marque)
                <span class="text-xs bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700 rounded px-2 py-0.5 text-indigo-700 dark:text-indigo-300">
                    Marque: {{ $this->product->marque->name }}
                </span>
            @endif
            @if($this->product?->categorie)
                <span class="text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded px-2 py-0.5 text-gray-600 dark:text-gray-300">
                    Categorie: {{ $this->product->categorie->name }}
                </span>
            @endif
            @if($this->product?->EAN)
                <span class="text-xs bg-white dark:bg-rose-800 border border-rose-200 dark:border-rose-700 rounded px-2 py-0.5 text-gray-600 dark:text-gray-300">
                EAN: {{ $this->product->EAN }}
            </span>
            @endif
        </div>
    </div>

    {{-- Tarification --}}
    <div>
        <flux:heading size="sm" class="mb-3">Tarification</flux:heading>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <flux:field>
                <flux:label>PU Achat HT</flux:label>
                <flux:input wire:model.live.number="pu_achat_ht" type="number" step="0.0001" min="0" placeholder="0.0000"/>
                <flux:error name="pu_achat_ht"/>
            </flux:field>

            <flux:field>
                <flux:label>Remise (%)</flux:label>
                <flux:input wire:model.live="taux_remise" type="number" step="0.01" min="0" max="100" placeholder="0.00"/>
                <flux:error name="taux_remise"/>
            </flux:field>

            <flux:field>
                <flux:label>TVA (%)</flux:label>
                <flux:input wire:model="tax" type="number" step="0.01" min="0" placeholder="0.00"/>
                <flux:error name="tax"/>
            </flux:field>
        </div>

        @if($pu_achat_ht > 0)
            <div class="mt-3 rounded-md bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-2 flex items-center justify-between text-sm">
                <span class="text-gray-500">PU Achat Net calculé</span>
                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($pu_achat_net, 4) }}</span>
            </div>
        @endif
    </div>

    {{-- Répartition par magasin --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <flux:heading size="sm">Répartition par magasin</flux:heading>
            <span class="text-sm font-semibold {{ $this->totalQuantite > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                Total : {{ $this->totalQuantite }} unité(s)
            </span>
        </div>

        @error('repartitions')
        <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
        @enderror

        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
            @foreach($this->magasins as $magasin)
                <div class="flex items-center gap-3 rounded-md border mb-3 border-gray-200 dark:border-gray-700 px-3 py-2 bg-white dark:bg-gray-800
                    {{ ($repartitions[$magasin->id] ?? 0) > 0 ? 'border-indigo-300 dark:border-indigo-600 bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                            {{ $magasin->name }}
                        </p>
                    </div>
                    <div class="w-28 shrink-0">
                        <flux:input
                            wire:model.live="repartitions.{{ $magasin->id }}"
                            type="number"
                            min="0"
                            step="1"
                            placeholder="0"
                            size="sm"
                        />
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
        <flux:button variant="ghost" x-on:click="$flux.modal('repartition-modal').close()">
            Annuler
        </flux:button>
        <flux:button wire:click="save" wire:loading.attr="disabled" variant="primary">
            <span wire:loading.remove wire:target="save">
                {{ $edit_mode ? 'Enregistrer les modifications' : 'Ajouter à la commande' }}
            </span>
            <span wire:loading wire:target="save">Enregistrement...</span>
        </flux:button>
    </div>
</div>
