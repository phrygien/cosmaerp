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

<style>
    @import url('https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap');

    #facture-print * {
        font-family: 'Josefin Sans', sans-serif;
        letter-spacing: 0.02em;
    }

    /* ── Section label (style "ADRESSE DE LIVRAISON") ── */
    .fac-section-label {
        display: inline-block;
        background: #811844;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        padding: 2px 10px;
        margin-bottom: 10px;
    }

    /* ── Bandeau titre principal ── */
    .fac-header-bar {
        background: #811844;
        padding: 28px 36px 24px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .fac-header-bar h1 {
        color: #fff;
        font-size: 1.6rem;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
    }
    .fac-header-sub { color: #f9a8d4; font-size: 0.8rem; margin-top: 4px; }
    .fac-header-num-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: #f9a8d4;
        margin-bottom: 4px;
        text-align: right;
    }
    .fac-header-num-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #fff;
        text-align: right;
    }

    /* ── Blocs info encadrés ── */
    .fac-info-box {
        border: 1px solid #d1d5db;
        padding: 14px 16px;
    }

    /* ── Table lignes ── */
    .fac-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .fac-table thead tr {
        border-bottom: 2px solid #811844;
    }
    .fac-table thead th {
        padding: 10px 10px;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #811844;
        background: #fff5f7;
    }
    .fac-table tbody tr { border-bottom: 1px solid #f3f4f6; }
    .fac-table tbody tr:hover { background: #fff5f7; }
    .fac-table td { padding: 10px 10px; vertical-align: middle; }

    /* ── Remise badge ── */
    .remise-badge {
        display: inline-flex;
        align-items: center;
        background: #fce7f3;
        color: #9f1239;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 8px;
        letter-spacing: 0.06em;
    }

    /* ── Totaux ── */
    .fac-totaux { width: 100%; max-width: 340px; margin-left: auto; font-size: 0.82rem; }
    .fac-totaux-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .fac-totaux-label { color: #6b7280; }
    .fac-totaux-value { font-weight: 600; color: #111; }
    .fac-totaux-ttc {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        background: #811844;
        padding: 12px 16px;
        margin-top: 10px;
    }
    .fac-totaux-ttc-label { color: #f9a8d4; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.12em; }
    .fac-totaux-ttc-value { color: #fff; font-size: 1.2rem; font-weight: 700; }
    .fac-economie {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fce7f3;
        padding: 8px 16px;
        margin-top: 6px;
    }
    .fac-economie-label { font-size: 0.72rem; font-weight: 700; color: #9f1239; }
    .fac-economie-value { font-size: 0.85rem; font-weight: 700; color: #9f1239; }

    /* ── Séparateur ── */
    .fac-divider { border: none; border-top: 1.5px solid #fce7f3; margin: 24px 0; }

    /* ── Pied de page ── */
    .fac-footer {
        display: flex;
        flex-wrap: wrap;
        gap: 32px;
        padding: 20px 36px;
        border-top: 1px solid #f3f4f6;
        background: #fafafa;
    }
    .fac-footer-label {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #811844;
        margin-bottom: 4px;
    }
    .fac-footer-value { font-size: 0.82rem; font-weight: 600; color: #374151; }

    /* ── Statut badge ── */
    .fac-status {
        display: inline-block;
        background: #fce7f3;
        color: #9f1239;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 3px 10px;
    }

    @media print {
        body > *:not(#facture-print) { display: none !important; }
        #facture-print { box-shadow: none !important; border: none !important; }
        nav, header, footer, [wire\:click], flux-button { display: none !important; }
        .fac-header-bar,
        .fac-totaux-ttc,
        .fac-economie,
        .fac-section-label,
        .remise-badge,
        .fac-status,
        .fac-table thead th {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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

    <div class="border border-zinc-200 overflow-hidden" id="facture-print">

        {{-- ══ BANDEAU TITRE ══ --}}
        <div class="fac-header-bar">
            <div>
                <h1>Facture</h1>
                <p class="fac-header-sub">Commande : {{ $this->commande->libelle }}</p>
            </div>
            <div>
                <p class="fac-header-num-label">N° Facture</p>
                <p class="fac-header-num-value">{{ $this->facture->numero }}</p>
            </div>
        </div>

        <div class="bg-white p-8">

            {{-- ══ META : FOURNISSEUR + DÉTAILS ══ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-8">

                {{-- Fournisseur --}}
                <div>
                    <span class="fac-section-label">Fournisseur</span>
                    <div class="fac-info-box">
                        <p class="font-bold text-base" style="color:#811844;">
                            {{ $this->commande->fournisseur?->name ?? '—' }}
                        </p>
                        <div class="mt-2 space-y-1 text-sm text-zinc-500" style="line-height:1.8;">
                            @if($this->commande->fournisseur?->email)
                                <p>{{ $this->commande->fournisseur->email }}</p>
                            @endif
                            @if($this->commande->fournisseur?->telephone)
                                <p>Tél : {{ $this->commande->fournisseur->telephone }}</p>
                            @endif
                            @if($this->commande->fournisseur?->adresse)
                                <p>{{ $this->commande->fournisseur->adresse }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Détails facture --}}
                <div>
                    <span class="fac-section-label">Détails</span>
                    <div class="fac-info-box" style="line-height:2;">
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
                                <span class="font-semibold text-zinc-800">{{ $value }}</span>
                            </div>
                        @endforeach

                        @if($this->commande->magasinLivraison)
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Livraison</span>
                                <span class="font-semibold text-zinc-800">{{ $this->commande->magasinLivraison->name }}</span>
                            </div>
                        @endif

                        @if($this->commande->remise_facture > 0)
                            <div class="flex justify-between text-sm items-center">
                                <span class="text-zinc-500">Remise commande</span>
                                <span class="remise-badge">{{ $this->commande->remise_facture }}%</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-sm items-center pt-1">
                            <span class="text-zinc-500">Statut</span>
                            <span class="fac-status">{{ $this->commande->status->label() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="fac-divider">

            {{-- ══ TABLEAU LIGNES ══ --}}
            <div>
                <span class="fac-section-label">Lignes de facturation</span>
            </div>
            <div class="overflow-x-auto mt-3">
                <table class="fac-table">
                    <thead>
                    <tr>
                        <th class="text-left">Produit</th>
                        <th class="text-right">Qté</th>
                        <th class="text-right">PU HT</th>
                        <th class="text-right">Montant HT</th>
                        <th class="text-right">Remise</th>
                        <th class="text-right">HT net</th>
                        <th class="text-right">Net TTC</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($this->facture->detailsFacture as $ligne)
                        @php
                            $detailCommande = $ligne->detailCommande;
                            $product        = $detailCommande?->product;
                            $puHT           = $detailCommande?->pu_achat_HT
                                ?? ($ligne->quantite_commande > 0 ? $ligne->montant_HT / $ligne->quantite_commande : 0);
                            $tauxRemise     = $detailCommande?->taux_remise ?? 0;
                            $hasRemise      = $ligne->montant_remise > 0;
                        @endphp
                        <tr>
                            {{-- Produit --}}
                            <td>
                                <p class="font-semibold text-zinc-800">{{ $product?->designation ?? '—' }}</p>
                                @if($product?->reference)
                                    <p class="text-xs text-zinc-400 mt-0.5">Réf. {{ $product->reference }}</p>
                                @endif
                                @if($product?->code_barre ?? $product?->barcode ?? null)
                                    <p class="text-xs text-zinc-400">CB : {{ $product->code_barre ?? $product->barcode }}</p>
                                @endif
                                @if($product?->famille?->name ?? $product?->category?->name ?? null)
                                    <p class="text-xs text-zinc-400">{{ $product->famille?->name ?? $product->category?->name }}</p>
                                @endif
                                @if($product?->unite ?? $product?->conditionnement ?? null)
                                    <p class="text-xs text-zinc-400">Unité : {{ $product->unite ?? $product->conditionnement }}</p>
                                @endif
                                @if($detailCommande?->tax)
                                    <p class="text-xs text-zinc-400">TVA : {{ $detailCommande->tax }}%</p>
                                @endif
                            </td>

                            <td class="text-right text-zinc-600">{{ $ligne->quantite_commande }}</td>

                            <td class="text-right text-zinc-600">{{ number_format($puHT, 2, ',', ' ') }} EUR</td>

                            <td class="text-right text-zinc-600">{{ number_format($ligne->montant_HT, 2, ',', ' ') }} EUR</td>

                            <td class="text-right">
                                @if($hasRemise)
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="font-semibold" style="color:#811844;">
                                            - {{ number_format($ligne->montant_remise, 2, ',', ' ') }} EUR
                                        </span>
                                        @if($tauxRemise)
                                            <span class="remise-badge">{{ $tauxRemise }}%</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-zinc-300">—</span>
                                @endif
                            </td>

                            <td class="text-right text-zinc-600">{{ number_format($ligne->montant_final_ht, 2, ',', ' ') }} EUR</td>

                            <td class="text-right font-semibold" style="color:#811844;">
                                {{ number_format($ligne->montant_final_net, 2, ',', ' ') }} EUR
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ══ TOTAUX ══ --}}
            @php
                $totalHT          = $this->facture->detailsFacture->sum('montant_HT');
                $totalRemise      = $this->facture->detailsFacture->sum('montant_remise');
                $totalHtNet       = $this->facture->detailsFacture->sum('montant_final_ht');
                $totalTtc         = $this->facture->detailsFacture->sum('montant_final_net');
                $montantTva       = $totalTtc - $totalHtNet;
                $tauxRemiseGlobal = $this->facture->remise ?? 0;
            @endphp
            <div class="mt-10">
                <span class="fac-section-label">Récapitulatif</span>
                <div class="fac-totaux mt-3">

                    <div class="fac-totaux-row">
                        <span class="fac-totaux-label">Total HT brut</span>
                        <span class="fac-totaux-value">{{ number_format($totalHT, 2, ',', ' ') }} EUR</span>
                    </div>

                    @if($totalRemise > 0)
                        <div class="fac-totaux-row">
                            <span class="fac-totaux-label">
                                Remises lignes
                                @if($tauxRemiseGlobal > 0)
                                    <span class="text-xs text-zinc-400 ml-1">(dont {{ $tauxRemiseGlobal }}% global)</span>
                                @endif
                            </span>
                            <span class="font-semibold" style="color:#811844;">
                                - {{ number_format($totalRemise, 2, ',', ' ') }} EUR
                            </span>
                        </div>
                    @endif

                    <div class="fac-totaux-row">
                        <span class="fac-totaux-label">Total HT net</span>
                        <span class="fac-totaux-value">{{ number_format($totalHtNet, 2, ',', ' ') }} EUR</span>
                    </div>

                    @if($this->facture->tax > 0)
                        <div class="fac-totaux-row">
                            <span class="fac-totaux-label">TVA ({{ $this->facture->tax }}%)</span>
                            <span class="fac-totaux-value">{{ number_format($montantTva, 2, ',', ' ') }} EUR</span>
                        </div>
                    @endif

                    <div class="fac-totaux-ttc">
                        <span class="fac-totaux-ttc-label">Total TTC</span>
                        <span class="fac-totaux-ttc-value">
                            {{ number_format($this->facture->montant ?? $totalTtc, 2, ',', ' ') }} EUR
                        </span>
                    </div>

                    @if($totalRemise > 0)
                        <div class="fac-economie">
                            <span class="fac-economie-label">💰 Économie réalisée</span>
                            <span class="fac-economie-value">{{ number_format($totalRemise, 2, ',', ' ') }} EUR</span>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- ══ PIED DE PAGE ══ --}}
        @if($this->commande->date_cloture || $this->commande->date_reception)
            <div class="fac-footer">
                @if($this->commande->date_cloture)
                    <div>
                        <p class="fac-footer-label">Date de clôture</p>
                        <p class="fac-footer-value">{{ $this->commande->date_cloture->translatedFormat('d F Y') }}</p>
                    </div>
                @endif
                @if($this->commande->date_reception)
                    <div>
                        <p class="fac-footer-label">Date de réception</p>
                        <p class="fac-footer-value">{{ $this->commande->date_reception->translatedFormat('d F Y') }}</p>
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>
