<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Commande;
use App\Models\Facture;

new class extends Component
{
    public int $commande_id;

    public function mount(int $commande_id): void
    {
        $this->commande_id = $commande_id;
    }

    #[Computed]
    public function commande()
    {
        return Commande::with([
            'fournisseur',
            'magasinLivraison',
        ])->findOrFail($this->commande_id);
    }

    #[Computed]
    public function facture()
    {
        return Facture::with([
            'detailsFacture.detailCommande.product',
        ])
            ->where('commande_id', $this->commande_id)
            ->where('state', 1)
            ->firstOrFail();
    }
};
?>

<div class="max-w-5xl mx-auto">

    <flux:breadcrumbs class="mb-5">
        <flux:breadcrumbs.item href="{{ route('orders.list') }}" wire:navigate>Précommande</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Facture</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Facture N°{{ $this->facture->numero }}</flux:heading>
            <p class="text-sm text-zinc-500 mt-1">Commande : {{ $this->commande->libelle }}</p>
        </div>

        <flux:button variant="primary" icon="printer" onclick="window.print()">
            Imprimer
        </flux:button>
    </div>

    <flux:card class="p-8" id="facture-print">

        {{-- En-tête facture --}}
        <div class="flex flex-col sm:flex-row sm:justify-between gap-6 mb-8">

            {{-- Infos fournisseur (via commande, car Facture::forfaisseur a une typo et fournisseur_id peut être null) --}}
            <div>
                <p class="text-xs font-semibold uppercase text-zinc-400 mb-2">Fournisseur</p>
                <p class="font-bold text-lg text-zinc-800 dark:text-zinc-100">
                    {{ $this->commande->fournisseur?->name ?? '—' }}
                </p>
                @if($this->commande->fournisseur?->email)
                    <p class="text-sm text-zinc-500">{{ $this->commande->fournisseur->email }}</p>
                @endif
                @if($this->commande->fournisseur?->telephone)
                    <p class="text-sm text-zinc-500">{{ $this->commande->fournisseur->telephone }}</p>
                @endif
                @if($this->commande->fournisseur?->adresse)
                    <p class="text-sm text-zinc-500">{{ $this->commande->fournisseur->adresse }}</p>
                @endif
            </div>

            {{-- Infos facture --}}
            <div class="sm:text-right">
                <p class="text-xs font-semibold uppercase text-zinc-400 mb-2">Détails</p>
                <div class="space-y-1 text-sm">
                    <div class="flex sm:justify-end gap-2">
                        <span class="text-zinc-500">N° Facture :</span>
                        <span class="font-semibold">{{ $this->facture->numero }}</span>
                    </div>
                    {{-- Facture a date_commande, pas Commande::created_at --}}
                    <div class="flex sm:justify-end gap-2">
                        <span class="text-zinc-500">Date commande :</span>
                        <span>
                            {{ $this->facture->date_commande
                                ? \Carbon\Carbon::parse($this->facture->date_commande)->translatedFormat('d F Y')
                                : ($this->commande->created_at?->translatedFormat('d F Y') ?? '—') }}
                        </span>
                    </div>
                    <div class="flex sm:justify-end gap-2">
                        <span class="text-zinc-500">Date de réception :</span>
                        <span>
                            {{ $this->facture->date_reception
                                ? \Carbon\Carbon::parse($this->facture->date_reception)->translatedFormat('d F Y')
                                : '—' }}
                        </span>
                    </div>
                    @if($this->commande->magasinLivraison)
                        <div class="flex sm:justify-end gap-2">
                            <span class="text-zinc-500">Livraison :</span>
                            <span>{{ $this->commande->magasinLivraison->name }}</span>
                        </div>
                    @endif
                    <div class="flex sm:justify-end gap-2 mt-2">
                        <flux:badge size="sm" :color="$this->commande->status->color()">
                            {{ $this->commande->status->label() }}
                        </flux:badge>
                    </div>
                </div>
            </div>
        </div>

        <flux:separator class="mb-6" />

        {{-- Tableau des lignes --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="text-left py-3 px-2 font-semibold text-zinc-600 dark:text-zinc-300">Produit</th>
                    <th class="text-right py-3 px-2 font-semibold text-zinc-600 dark:text-zinc-300">Qté</th>
                    <th class="text-right py-3 px-2 font-semibold text-zinc-600 dark:text-zinc-300">PU HT</th>
                    <th class="text-right py-3 px-2 font-semibold text-zinc-600 dark:text-zinc-300">Montant HT</th>
                    <th class="text-right py-3 px-2 font-semibold text-zinc-600 dark:text-zinc-300">Remise</th>
                    <th class="text-right py-3 px-2 font-semibold text-zinc-600 dark:text-zinc-300">HT net</th>
                    <th class="text-right py-3 px-2 font-semibold text-zinc-600 dark:text-zinc-300">Net TTC</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->facture->detailsFacture as $ligne)
                    @php
                        {{-- PU HT depuis DetailCommande si disponible, sinon calcul depuis DetailFacture --}}
                        $detailCommande = $ligne->detailCommande;
                        $puHT = $detailCommande?->pu_achat_HT
                            ?? ($ligne->quantite_commande > 0 ? $ligne->montant_HT / $ligne->quantite_commande : 0);
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="py-3 px-2">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                {{ $detailCommande?->product?->designation ?? '—' }}
                            </p>
                            @if($detailCommande?->product?->reference)
                                <p class="text-xs text-zinc-400">Réf. {{ $detailCommande->product->reference }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-right text-zinc-700 dark:text-zinc-300">
                            {{ $ligne->quantite_commande }}
                        </td>
                        <td class="py-3 px-2 text-right text-zinc-700 dark:text-zinc-300">
                            {{ number_format($puHT, 2, ',', ' ') }} €
                        </td>
                        <td class="py-3 px-2 text-right text-zinc-700 dark:text-zinc-300">
                            {{ number_format($ligne->montant_HT, 2, ',', ' ') }} €
                        </td>
                        <td class="py-3 px-2 text-right">
                            @if($ligne->montant_remise > 0)
                                <span class="text-red-500">
                                    - {{ number_format($ligne->montant_remise, 2, ',', ' ') }} €
                                    @if($detailCommande?->taux_remise)
                                        <span class="text-xs text-zinc-400">({{ $detailCommande->taux_remise }}%)</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-2 text-right text-zinc-700 dark:text-zinc-300">
                            {{ number_format($ligne->montant_final_ht, 2, ',', ' ') }} €
                        </td>
                        <td class="py-3 px-2 text-right font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ number_format($ligne->montant_final_net, 2, ',', ' ') }} €
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <flux:separator class="my-6" />

        {{-- Totaux --}}
        <div class="flex justify-end">
            <div class="w-full sm:w-72 space-y-2 text-sm">

                <div class="flex justify-between">
                    <span class="text-zinc-500">Total HT</span>
                    <span class="font-medium">
                        {{ number_format($this->facture->detailsFacture->sum('montant_HT'), 2, ',', ' ') }} €
                    </span>
                </div>

                @if($this->facture->remise > 0)
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Remise globale ({{ $this->facture->remise }}%)</span>
                        <span class="text-red-500 font-medium">
                            - {{ number_format($this->facture->detailsFacture->sum('montant_remise'), 2, ',', ' ') }} €
                        </span>
                    </div>
                @endif

                <div class="flex justify-between">
                    <span class="text-zinc-500">Total HT net</span>
                    <span class="font-medium">
                        {{ number_format($this->facture->detailsFacture->sum('montant_final_ht'), 2, ',', ' ') }} €
                    </span>
                </div>

                @if($this->facture->tax > 0)
                    @php
                        $totalHtNet = $this->facture->detailsFacture->sum('montant_final_ht');
                        $totalTtc   = $this->facture->detailsFacture->sum('montant_final_net');
                        $montantTva = $totalTtc - $totalHtNet;
                    @endphp
                    <div class="flex justify-between">
                        <span class="text-zinc-500">TVA ({{ $this->facture->tax }}%)</span>
                        <span class="font-medium">
                            {{ number_format($montantTva, 2, ',', ' ') }} €
                        </span>
                    </div>
                @endif

                <flux:separator />

                <div class="flex justify-between text-base font-bold text-zinc-800 dark:text-zinc-100 pt-1">
                    <span>Total TTC</span>
                    {{-- Utilise montant de Facture comme source de vérité, avec fallback sur la somme des lignes --}}
                    <span>
                        {{ number_format(
                            $this->facture->montant ?? $this->facture->detailsFacture->sum('montant_final_net'),
                            2, ',', ' '
                        ) }} €
                    </span>
                </div>
            </div>
        </div>

        {{-- Dates clés depuis Commande --}}
        @if($this->commande->date_cloture || $this->commande->date_reception)
            <flux:separator class="my-6" />
            <div class="flex flex-wrap gap-6 text-sm text-zinc-500">
                @if($this->commande->date_cloture)
                    <div>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">Date de clôture :</span>
                        {{ $this->commande->date_cloture->translatedFormat('d F Y') }}
                    </div>
                @endif
                @if($this->commande->date_reception)
                    <div>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">Date de réception :</span>
                        {{ $this->commande->date_reception->translatedFormat('d F Y') }}
                    </div>
                @endif
            </div>
        @endif

    </flux:card>

</div>

<style>
    @media print {
        body > *:not(#facture-print) { display: none !important; }
        #facture-print { box-shadow: none !important; border: none !important; }
        nav, header, footer, [wire\:click], flux-button { display: none !important; }
    }
</style>
