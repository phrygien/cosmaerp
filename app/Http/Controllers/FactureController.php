<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class FactureController extends Controller
{
    /**
     * Génère le PDF de facture.
     *
     * Route :
     *   Route::get('/facture/pdf/{facture}', FactureController::class)
     *       ->name('facture.pdf');
     */
    public function __invoke(Request $request, Facture $facture): \Illuminate\Http\Response
    {
        $facture->load([
            'forfaisseur',
            'commande.magasinLivraison',
            'detailsFacture.detailCommande.product',
        ]);

        $fournisseur = $facture->forfaisseur;
        $commande    = $facture->commande;
        $magasin     = $commande?->magasinLivraison;

        // ── Lignes du tableau ──────────────────────────────────────────────
        $lignes = collect();

        foreach ($facture->detailsFacture as $detailFacture) {
            $dc      = $detailFacture->detailCommande;
            $product = $dc?->product;

            $lignes->push([
                'ref_interne'     => $product?->product_code ?? '—',
                'designation'     => $product?->designation  ?? '—',
                'article'         => $product?->article      ?? '—',
                'ref_fournisseur' => $dc?->ref_fournisseur   ?? '—',
                'ean'             => $product?->EAN           ?? '—',
                'qte_commandee'   => $detailFacture->quantite_commande ?? 0,
                'pu_ht'           => $dc?->pu_achat_HT        ?? 0,
                'taux_remise'     => $dc?->taux_remise         ?? 0,
                'montant_ht'      => $detailFacture->montant_HT        ?? 0,
                'montant_remise'  => $detailFacture->montant_remise    ?? 0,
                'montant_net_ht'  => $detailFacture->montant_final_ht  ?? 0,
                'tva'             => $dc?->tax                 ?? 0,
                'montant_ttc'     => $detailFacture->montant_final_net ?? 0,
            ]);
        }

        // ── Totaux ────────────────────────────────────────────────────────
        $totalHT     = $lignes->sum('montant_ht');
        $totalRemise = $lignes->sum('montant_remise');
        $totalNetHT  = $lignes->sum('montant_net_ht');
        $totalTTC    = $lignes->sum('montant_ttc');
        $totalTVA    = $totalTTC - $totalNetHT;

        $data = [
            'facture'          => $facture,
            'fournisseur'      => $fournisseur,
            'commande'         => $commande,
            'magasin'          => $magasin,
            'lignes'           => $lignes,
            'total_ht'         => $totalHT,
            'total_remise'     => $totalRemise,
            'total_net_ht'     => $totalNetHT,
            'total_tva'        => $totalTVA,
            'total_ttc'        => $totalTTC,
            'total_articles'   => $lignes->count(),
            'date_impression'  => now()->format('d/m/Y'),
            'heure_impression' => now()->format('H:i'),
        ];

        $pdf = Pdf::loadView('pdf.facture', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
                'dpi'                  => 150,
            ]);

        $filename = 'facture-' . ($facture->numero ?? $facture->id) . '.pdf';

        return $pdf->stream($filename);
    }
}
