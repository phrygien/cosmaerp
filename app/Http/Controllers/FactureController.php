<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class FactureController extends Controller
{
    /**
     * Génère le PDF de facture calqué sur la vue Livewire existante.
     *
     * Route :
     *   Route::get('/facture/pdf/{facture}', FactureController::class)
     *       ->name('facture.pdf');
     */
    public function __invoke(Request $request, Facture $facture): \Illuminate\Http\Response
    {
        $facture->load([
            'forfaisseur',
            'commande.fournisseur',
            'commande.magasinLivraison',
            'detailsFacture.detailCommande.product',
        ]);

        $commande    = $facture->commande;
        $fournisseur = $commande?->fournisseur ?? $facture->forfaisseur;
        $magasin     = $commande?->magasinLivraison;

        // ── Lignes ────────────────────────────────────────────────────────
        $lignes = collect();

        foreach ($facture->detailsFacture as $df) {
            $dc      = $df->detailCommande;
            $product = $dc?->product;

            $puHT = $dc?->pu_achat_HT
                ?? ($df->quantite_commande > 0 ? $df->montant_HT / $df->quantite_commande : 0);

            $lignes->push([
                'designation'    => $product?->designation ?? '—',
                'tva'            => $dc?->tax ?? 0,
                'qte'            => $df->quantite_commande ?? 0,
                'pu_ht'          => $puHT,
                'montant_ht'     => $df->montant_HT ?? 0,
                'montant_remise' => $df->montant_remise ?? 0,
                'taux_remise'    => $dc?->taux_remise ?? 0,
                'montant_net_ht' => $df->montant_final_ht ?? 0,
                'montant_ttc'    => $df->montant_final_net ?? 0,
            ]);
        }

        // ── Totaux ────────────────────────────────────────────────────────
        $totalHT     = $lignes->sum('montant_ht');
        $totalRemise = $lignes->sum('montant_remise');
        $totalNetHT  = $lignes->sum('montant_net_ht');
        $totalTTC    = $facture->montant ?? $lignes->sum('montant_ttc');
        $totalTVA    = $totalTTC - $totalNetHT;

        $data = [
            'facture'          => $facture,
            'commande'         => $commande,
            'fournisseur'      => $fournisseur,
            'magasin'          => $magasin,
            'lignes'           => $lignes,
            'total_ht'         => $totalHT,
            'total_remise'     => $totalRemise,
            'total_net_ht'     => $totalNetHT,
            'total_tva'        => $totalTVA,
            'total_ttc'        => $totalTTC,
            'date_impression'  => now()->format('d/m/Y'),
            'heure_impression' => now()->format('H:i'),
        ];

        $pdf = Pdf::loadView('pdf.facture', $data)
            ->setPaper('a4', 'portrait')
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
