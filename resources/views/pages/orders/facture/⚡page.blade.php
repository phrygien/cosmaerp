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

{{-- Style impression --}}
<style>
    .fac-header-bar { background: #831843; padding: 28px 36px 24px; display: flex; justify-content: space-between; align-items: flex-start; }
    .fac-header-bar h1 { color: #fff; }
    @media print {
        body > *:not(#facture-print) { display: none !important; }
        #facture-print { box-shadow: none !important; border: none !important; }
        nav, header, footer, [wire\:click], flux-button { display: none !important; }
        .fac-header-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

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
        <flux:button variant="primary" icon="printer" onclick="window.print()">Imprimer</flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden" id="facture-print">

        {{-- Bandeau en-tête rose foncé --}}
        <div class="fac-header-bar flex flex-col sm:flex-row sm:justify-between gap-4"
             style="background:#831843; padding: 28px 36px 24px;">
            <div>
                <h1 class="text-2xl font-medium text-white tracking-wide mb-1">Facture</h1>
                <p class="text-sm" style="color:#fecdd3;">Commande : {{ $this->commande->libelle }}</p>
            </div>
            <div class="sm:text-right">
                <p class="text-xs uppercase tracking-widest mb-1" style="color:#fecdd3;">N° Facture</p>
                <p class="text-xl font-medium text-white">{{ $this->facture->numero }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 p-8 sm:p-9">

            {{-- Meta : Fournisseur + Détails --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-8">

                {{-- Fournisseur --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-zinc-400 mb-3">Fournisseur</p>
                    <p class="text-base font-medium" style="color:#be185d;">
                        {{ $this->commande->fournisseur?->name ?? '—' }}
                    </p>
                    <div class="mt-2 space-y-0.5 text-sm text-zinc-500">
                        @if($this->commande->fournisseur?->email)
                            <p>{{ $this->commande->fournisseur->email }}</p>
                        @endif
                        @if($this->commande->fournisseur?->telephone)
                            <p>{{ $this->commande->fournisseur->telephone }}</p>
                        @endif
                        @if($this->commande->fournisseur?->adresse)
                            <p>{{ $this->commande->fournisseur->adresse }}</p>
                        @endif
                    </div>
                </div>

                {{-- Détails facture --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-zinc-400 mb-3">Détails</p>
                    <div class="space-y-1.5">
                        @foreach([
                            ['Date commande', $this->facture->date_commande
                                ? \Carbon\Carbon::parse($this->facture->date_commande)->translatedFormat('d F Y')
                                : ($this->commande->created_at?->translatedFormat('d F Y') ?? '—')],
                            ['Date réception', $this->facture->date_reception
                                ? \Carbon\Carbon::parse($this->facture->date_reception)->translatedFormat('d F Y')
                                : '—'],
                        ] as [$label, $value])
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">{{ $label }}</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $value }}</span>
                            </div>
                        @endforeach
                        @if($this->commande->magasinLivraison)
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Livraison</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $this->commande->magasinLivraison->name }}
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-end pt-1">
                            <span class="inline-block text-xs px-3 py-1 rounded-full font-medium"
                                  style="background:#fce7f3; color:#9f1239;">
                                {{ $this->commande->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Séparateur accent --}}
            <div style="border-top: 1.5px solid #fce7f3;" class="mb-6"></div>

            {{-- Tableau lignes --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr style="border-bottom: 1.5px solid #fce7f3;">
                        <th class="text-left py-3 px-2.5 font-medium text-xs uppercase tracking-wider" style="color:#9f1239;">Produit</th>
                        <th class="text-right py-3 px-2.5 font-medium text-xs uppercase tracking-wider" style="color:#9f1239;">Qté</th>
                        <th class="text-right py-3 px-2.5 font-medium text-xs uppercase tracking-wider" style="color:#9f1239;">PU HT</th>
                        <th class="text-right py-3 px-2.5 font-medium text-xs uppercase tracking-wider" style="color:#9f1239;">Montant HT</th>
                        <th class="text-right py-3 px-2.5 font-medium text-xs uppercase tracking-wider" style="color:#9f1239;">Remise</th>
                        <th class="text-right py-3 px-2.5 font-medium text-xs uppercase tracking-wider" style="color:#9f1239;">HT net</th>
                        <th class="text-right py-3 px-2.5 font-medium text-xs uppercase tracking-wider" style="color:#9f1239;">Net TTC</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->facture->detailsFacture as $ligne)
                        @php
                            $detailCommande = $ligne->detailCommande;
                            $puHT = $detailCommande?->pu_achat_HT
                                ?? ($ligne->quantite_commande > 0 ? $ligne->montant_HT / $ligne->quantite_commande : 0);
                        @endphp
                        <tr class="hover:bg-rose-50/40 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="py-3 px-2.5">
                                <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $detailCommande?->product?->designation ?? '—' }}
                                </p>
                                @if($detailCommande?->product?->reference)
                                    <p class="text-xs text-zinc-400 mt-0.5">Réf. {{ $detailCommande->product->reference }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-2.5 text-right text-zinc-600 dark:text-zinc-400">{{ $ligne->quantite_commande }}</td>
                            <td class="py-3 px-2.5 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($puHT, 2, ',', ' ') }} €</td>
                            <td class="py-3 px-2.5 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($ligne->montant_HT, 2, ',', ' ') }} €</td>
                            <td class="py-3 px-2.5 text-right">
                                @if($ligne->montant_remise > 0)
                                    <span style="color:#be185d;">
                                        - {{ number_format($ligne->montant_remise, 2, ',', ' ') }} €
                                        @if($detailCommande?->taux_remise)
                                            <span class="text-xs text-zinc-400">({{ $detailCommande->taux_remise }}%)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-zinc-300">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-2.5 text-right text-zinc-600 dark:text-zinc-400">{{ number_format($ligne->montant_final_ht, 2, ',', ' ') }} €</td>
                            <td class="py-3 px-2.5 text-right font-medium" style="color:#9f1239;">
                                {{ number_format($ligne->montant_final_net, 2, ',', ' ') }} €
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totaux --}}
            <div class="flex justify-end mt-8">
                <div class="w-full sm:w-72 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Total HT</span>
                        <span class="font-medium">{{ number_format($this->facture->detailsFacture->sum('montant_HT'), 2, ',', ' ') }} €</span>
                    </div>
                    @if($this->facture->remise > 0)
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Remise globale ({{ $this->facture->remise }}%)</span>
                            <span class="font-medium" style="color:#be185d;">
                                - {{ number_format($this->facture->detailsFacture->sum('montant_remise'), 2, ',', ' ') }} €
                            </span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Total HT net</span>
                        <span class="font-medium">{{ number_format($this->facture->detailsFacture->sum('montant_final_ht'), 2, ',', ' ') }} €</span>
                    </div>
                    @if($this->facture->tax > 0)
                        @php
                            $totalHtNet = $this->facture->detailsFacture->sum('montant_final_ht');
                            $totalTtc   = $this->facture->detailsFacture->sum('montant_final_net');
                            $montantTva = $totalTtc - $totalHtNet;
                        @endphp
                        <div class="flex justify-between">
                            <span class="text-zinc-500">TVA ({{ $this->facture->tax }}%)</span>
                            <span class="font-medium">{{ number_format($montantTva, 2, ',', ' ') }} €</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-baseline px-4 py-3 rounded-lg mt-2" style="background:#831843;">
                        <span class="text-sm" style="color:#fecdd3;">Total TTC</span>
                        <span class="text-lg font-medium text-white">
                            {{ number_format(
                                $this->facture->montant ?? $this->facture->detailsFacture->sum('montant_final_net'),
                                2, ',', ' '
                            ) }} €
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pied de page dates clés --}}
        @if($this->commande->date_cloture || $this->commande->date_reception)
            <div class="flex flex-wrap gap-8 px-9 py-5 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
                @if($this->commande->date_cloture)
                    <div>
                        <p class="text-xs uppercase tracking-widest text-zinc-400 mb-1">Date de clôture</p>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->commande->date_cloture->translatedFormat('d F Y') }}
                        </p>
                    </div>
                @endif
                @if($this->commande->date_reception)
                    <div>
                        <p class="text-xs uppercase tracking-widest text-zinc-400 mb-1">Date de réception</p>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ $this->commande->date_reception->translatedFormat('d F Y') }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>
