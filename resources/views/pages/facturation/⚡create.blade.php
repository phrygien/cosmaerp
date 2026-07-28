<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use App\Models\Facture;
use App\Models\Commande;
use App\Models\DetailCommande;
use App\Enums\TypeFacture;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    // ─── Champs facture ──────────────────────────────────────────
    #[Validate('required|string|max:100')]
    public string $numero         = '';

    #[Validate('required|string|max:255')]
    public string $libelle        = '';

    #[Validate('required|in:Commande,Retour')]
    public string $type           = TypeFacture::Commande->value;

    #[Validate('required|exists:commande,id')]
    public ?int   $commande_id    = null;

    #[Validate('required|date')]
    public string $date_commande  = '';

    #[Validate('nullable|date')]
    public string $date_reception = '';

    #[Validate('nullable|numeric|min:0|max:100')]
    public ?float $remise         = null;

    #[Validate('nullable|numeric|min:0')]
    public ?float $tax            = null;

    public int $state = 0;

    // ─── Lignes de détail ────────────────────────────────────────
    public array $lignes = [];

    // ─── Info fournisseur (lecture seule, déduit de la commande) ─
    public ?string $fournisseurNom  = null;
    public ?string $fournisseurCode = null;

    public function mount(): void
    {
        $this->date_commande = now()->format('Y-m-d');
    }

    // ─── Toutes les commandes disponibles ────────────────────────
    #[Computed]
    public function commandes()
    {
        return Commande::with('fournisseur')
            ->where('status', \App\Enums\CommandeStatus::Recue)
            ->whereDoesntHave('factures', fn($q) => $q->where('state', 0))
            ->orderByDesc('created_at')
            ->get();
    }

    // ─── Totaux calculés ─────────────────────────────────────────
    #[Computed]
    public function totalHT(): float
    {
        return collect($this->lignes)
            ->sum(fn($l) => ($l['montant_HT'] ?? 0) * ($l['quantite_commande'] ?? 0));
    }

    #[Computed]
    public function totalRemise(): float
    {
        return $this->totalHT * (($this->remise ?? 0) / 100);
    }

    #[Computed]
    public function totalTax(): float
    {
        return ($this->totalHT - $this->totalRemise) * (($this->tax ?? 0) / 100);
    }

    #[Computed]
    public function totalNet(): float
    {
        return $this->totalHT - $this->totalRemise + $this->totalTax;
    }

    // ─── Watcher commande : déduire fournisseur + charger lignes ─
    public function updatedCommandeId(): void
    {
        $this->lignes          = [];
        $this->fournisseurNom  = null;
        $this->fournisseurCode = null;

        unset($this->totalHT, $this->totalRemise, $this->totalTax, $this->totalNet);

        if (! $this->commande_id) return;

        $commande = Commande::with('fournisseur')->find($this->commande_id);
        if (! $commande) return;

        // Déduit le fournisseur depuis la commande
        $this->fournisseurNom  = $commande->fournisseur?->name;
        $this->fournisseurCode = $commande->fournisseur?->code;

        // Pré-remplit le libellé si vide
        if (empty($this->libelle)) {
            $this->libelle = $commande->libelle ?? '';
        }

        // Charge les lignes depuis les détails de la commande
        $details = DetailCommande::with('product')
            ->where('commande_id', $this->commande_id)
            ->where('state', 1)
            ->get();

        foreach ($details as $detail) {
            $ht             = ($detail->pu_achat_HT ?? 0) * ($detail->quantite ?? 0);
            $remiseLigne    = $ht * (($detail->taux_remise ?? 0) / 100);
            $this->lignes[] = [
                'detail_commande_id' => $detail->id,
                'designation'        => $detail->product?->designation ?? '—',
                'quantite_commande'  => $detail->quantite,
                'montant_HT'         => $detail->pu_achat_HT,
                'taux_remise'        => $detail->taux_remise ?? 0,
                'montant_remise'     => round($remiseLigne, 4),
                'montant_final_ht'   => round($ht, 4),
                'montant_final_net'  => round($ht - $remiseLigne, 4),
                'state'              => 1,
            ];
        }

        $this->recalculerLignes();
    }

    public function updatedLignes(): void
    {
        $this->recalculerLignes();
    }

    public function recalculerLignes(): void
    {
        foreach ($this->lignes as &$ligne) {
            $ht                          = ($ligne['montant_HT'] ?? 0) * ($ligne['quantite_commande'] ?? 0);
            $remise                      = $ht * (($ligne['taux_remise'] ?? 0) / 100);
            $ligne['montant_remise']     = round($remise, 4);
            $ligne['montant_final_ht']   = round($ht, 4);
            $ligne['montant_final_net']  = round($ht - $remise, 4);
        }

        unset($this->totalHT, $this->totalRemise, $this->totalTax, $this->totalNet);
    }

    // ─── Sauvegarde ──────────────────────────────────────────────
    public function save(): void
    {
        $this->validate();

        if (empty($this->lignes)) {
            Flux::toast(
                heading: 'Lignes manquantes',
                text: 'Veuillez sélectionner une commande contenant des lignes.',
                variant: 'warning'
            );
            return;
        }

        $commande = Commande::find($this->commande_id);

        try {
            DB::beginTransaction();

            $facture = Facture::create([
                'numero'         => $this->numero,
                'libelle'        => $this->libelle,
                'type'           => $this->type,
                'fournisseur_id' => $commande->fournisseur_id,
                'commande_id'    => $this->commande_id,
                'date_commande'  => $this->date_commande,
                'date_reception' => $this->date_reception ?: null,
                'montant'        => $this->totalNet,
                'remise'         => $this->remise,
                'tax'            => $this->tax,
                'state'          => $this->state,
                'created_by'      => auth()->id(),
            ]);

            foreach ($this->lignes as $ligne) {
                $facture->detailsFacture()->create([
                    'detail_commande_id' => $ligne['detail_commande_id'],
                    'quantite_commande'  => $ligne['quantite_commande'],
                    'montant_HT'         => $ligne['montant_HT'],
                    'montant_remise'     => $ligne['montant_remise'],
                    'montant_final_ht'   => $ligne['montant_final_ht'],
                    'montant_final_net'  => $ligne['montant_final_net'],
                    'state'              => $ligne['state'],
                ]);
            }

            DB::commit();

            $this->dispatch('facture-created');

            Flux::toast(
                heading: 'Facture créée',
                text: "La facture \"{$facture->numero}\" a été créée avec succès.",
                variant: 'success'
            );

            $this->reset();
            $this->mount();

        } catch (\Exception $e) {
            DB::rollBack();

            Flux::toast(
                heading: 'Erreur',
                text: 'Impossible de créer la facture : ' . $e->getMessage(),
                variant: 'danger'
            );
        }
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->mount();
        $this->resetValidation();
    }
};
?>

<div class="max-w-5xl mx-auto">

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="#">Factures</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Créer</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-8">
        <flux:heading size="xl" level="1">Nouvelle Facture</flux:heading>

        <div class="flex items-center gap-2">
            <flux:button wire:click="resetForm">Réinitialiser</flux:button>
            <flux:button variant="danger" href="{{ route('facturation.list') }}" wire:navigate>Annuler</flux:button>
            <flux:button wire:click="save" variant="primary">Enregistrer</flux:button>
        </div>
    </div>

    <div class="space-y-10">

        {{-- ── Informations générales ──────────────────────────── --}}
        <flux:card class="p-0 overflow-hidden">
            <div class="px-6 pt-6">
                <flux:heading size="lg">Informations générales</flux:heading>
                <flux:subheading class="mt-1">Ces informations permettent d'identifier la facture et la commande associée.</flux:subheading>
            </div>

            <div class="mt-6 divide-y divide-zinc-200 dark:divide-zinc-700 border-t border-zinc-200 dark:border-zinc-700">

                {{-- Commande --}}
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Commande</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-md">
                        <flux:select wire:model.live="commande_id" placeholder="Sélectionner une commande">
                            <flux:select.option value="">— Choisir une commande —</flux:select.option>
                            @foreach($this->commandes as $c)
                                <flux:select.option value="{{ $c->id }}">
                                    {{ $c->libelle ?? 'Commande #' . $c->id }}
                                    @if($c->fournisseur) — {{ $c->fournisseur->name }} @endif
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="commande_id" />
                    </div>
                </div>

                {{-- Fournisseur déduit (lecture seule) --}}
                @if($fournisseurNom)
                    <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                        <flux:label class="sm:pt-1.5">Fournisseur</flux:label>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 max-w-md">
                                <i class="hgi-stroke hgi-building-04 text-xl text-indigo-400"></i>
                                <div>
                                    <p class="text-sm font-medium">{{ $fournisseurNom }}</p>
                                    @if($fournisseurCode)
                                        <p class="text-xs text-zinc-400">Code : {{ $fournisseurCode }}</p>
                                    @endif
                                </div>
                            </div>
                            <flux:description class="mt-2">Déduit automatiquement de la commande sélectionnée.</flux:description>
                        </div>
                    </div>
                @endif

                {{-- N° Facture --}}
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">N° Facture</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-md">
                        <flux:input wire:model="numero" placeholder="FAC-2024-001" />
                        <flux:description class="mt-2">Identifiant unique de la facture.</flux:description>
                        <flux:error name="numero" />
                    </div>
                </div>

                {{-- Type --}}
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Type</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0">
                        <flux:radio.group wire:model="type" variant="segmented">
                            @foreach(\App\Enums\TypeFacture::cases() as $case)
                                <flux:radio :label="$case->label()" :value="$case->value" />
                            @endforeach
                        </flux:radio.group>
                        <flux:error name="type" />
                    </div>
                </div>

                {{-- Libellé --}}
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Libellé</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-md">
                        <flux:input wire:model="libelle" placeholder="Description de la facture" />
                        <flux:error name="libelle" />
                    </div>
                </div>

                {{-- Date de commande --}}
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Date de commande</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-xs">
                        <flux:input wire:model="date_commande" type="date" />
                        <flux:error name="date_commande" />
                    </div>
                </div>

                {{-- Date de réception --}}
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Date de réception</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-xs">
                        <flux:input wire:model="date_reception" type="date" />
                        <flux:description class="mt-2">Optionnel.</flux:description>
                        <flux:error name="date_reception" />
                    </div>
                </div>

            </div>
        </flux:card>

        {{-- ── Lignes de facture ───────────────────────────────── --}}
        <flux:card class="p-0 overflow-hidden">
            <div class="px-6 pt-6 flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Lignes de facture</flux:heading>
                    <flux:subheading class="mt-1">Chargées automatiquement depuis les détails de la commande sélectionnée.</flux:subheading>
                </div>
                @if(count($lignes) > 0)
                    <flux:badge size="sm" color="blue">
                        {{ count($lignes) }} ligne{{ count($lignes) > 1 ? 's' : '' }}
                    </flux:badge>
                @endif
            </div>

            <div class="px-6 pb-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 mt-6">
                @if(count($lignes) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                <th class="pb-2 font-medium text-zinc-500 text-xs pr-3">Désignation</th>
                                <th class="pb-2 font-medium text-zinc-500 text-xs pr-3 w-20 text-right">Qté</th>
                                <th class="pb-2 font-medium text-zinc-500 text-xs pr-3 w-28 text-right">PU HT</th>
                                <th class="pb-2 font-medium text-zinc-500 text-xs pr-3 w-24 text-right">Remise %</th>
                                <th class="pb-2 font-medium text-zinc-500 text-xs w-28 text-right">Total net</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($lignes as $i => $ligne)
                                <tr wire:key="ligne-{{ $i }}">
                                    <td class="py-2.5 pr-3">
                                        <p class="font-medium text-sm">{{ $ligne['designation'] }}</p>
                                    </td>
                                    <td class="py-2.5 pr-3 text-right text-zinc-600 dark:text-zinc-400">
                                        {{ $ligne['quantite_commande'] }}
                                    </td>
                                    <td class="py-2.5 pr-3 text-right text-zinc-600 dark:text-zinc-400">
                                        {{ number_format($ligne['montant_HT'] ?? 0, 4, ',', ' ') }} €
                                    </td>
                                    <td class="py-2.5 pr-3 text-right">
                                        @if(($ligne['taux_remise'] ?? 0) > 0)
                                            <flux:badge size="sm" color="orange">{{ $ligne['taux_remise'] }} %</flux:badge>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right font-medium">
                                        {{ number_format($ligne['montant_final_net'] ?? 0, 2, ',', ' ') }} €
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-10 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <i class="hgi-stroke hgi-invoice-02 text-4xl text-zinc-300 mb-2"></i>
                        <p class="text-sm text-zinc-400">Aucune ligne</p>
                        <p class="text-xs text-zinc-400 mt-1">Les lignes seront chargées automatiquement depuis la commande sélectionnée</p>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- ── Récapitulatif financier ─────────────────────────── --}}
        <flux:card class="p-0 overflow-hidden">
            <div class="px-6 pt-6">
                <flux:heading size="lg">Récapitulatif financier</flux:heading>
                <flux:subheading class="mt-1">Appliquez une remise et/ou une taxe globale à la facture.</flux:subheading>
            </div>

            <div class="mt-6 divide-y divide-zinc-200 dark:divide-zinc-700 border-t border-zinc-200 dark:border-zinc-700">

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Remise globale (%)</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-xs">
                        <flux:input wire:model.live="remise" type="number" min="0" max="100" step="0.01" placeholder="0" />
                        <flux:error name="remise" />
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Taxe (%)</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-xs">
                        <flux:input wire:model.live="tax" type="number" min="0" step="0.01" placeholder="0" />
                        <flux:error name="tax" />
                    </div>
                </div>

                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Totaux</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between text-zinc-500">
                            <span>Total HT</span>
                            <span>{{ number_format($this->totalHT, 2, ',', ' ') }} €</span>
                        </div>
                        @if(($remise ?? 0) > 0)
                            <div class="flex justify-between text-orange-500">
                                <span>Remise ({{ $remise }} %)</span>
                                <span>− {{ number_format($this->totalRemise, 2, ',', ' ') }} €</span>
                            </div>
                        @endif
                        @if(($tax ?? 0) > 0)
                            <div class="flex justify-between text-zinc-500">
                                <span>Taxe ({{ $tax }} %)</span>
                                <span>+ {{ number_format($this->totalTax, 2, ',', ' ') }} €</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold text-base pt-2 border-t border-zinc-200 dark:border-zinc-700">
                            <span>Total Net</span>
                            <span class="text-indigo-600 dark:text-indigo-400">
                                {{ number_format($this->totalNet, 2, ',', ' ') }} €
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </flux:card>

        {{-- ── État ─────────────────────────────────────────────── --}}
        <flux:card class="p-0 overflow-hidden">
            <div class="px-6 pt-6">
                <flux:heading size="lg">État</flux:heading>
                <flux:subheading class="mt-1">Définissez si la facture est finalisée ou encore en cours.</flux:subheading>
            </div>

            <div class="mt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 px-6 py-6">
                    <flux:label class="sm:pt-1.5">Statut</flux:label>
                    <div class="mt-2 sm:col-span-2 sm:mt-0 max-w-xs">
                        <flux:radio.group wire:model="state" variant="segmented" class="w-full">
                            <flux:radio label="En cours" value="0" />
                            <flux:radio label="Validée"  value="1" />
                        </flux:radio.group>
                    </div>
                </div>
            </div>
        </flux:card>

    </div>

    <div class="mt-8 flex items-center justify-end gap-x-3">
        <flux:button wire:click="resetForm">Annuler les modifications</flux:button>
        <flux:button wire:click="save" variant="primary">Enregistrer la facture</flux:button>
    </div>

</div>
