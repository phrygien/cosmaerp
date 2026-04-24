<?php

namespace App\Http\Controllers;

use App\Models\BonCommande;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceptionController extends Controller
{
    /**
     * Génère le PDF de contrôle de réception pour un BonCommande donné.
     *
     * Route :
     *   Route::get('/reception/pdf/{bon}', ReceptionController::class)
     *       ->name('reception_commande.pdf');
     */
    public function __invoke(Request $request, BonCommande $bon): \Illuminate\Http\Response
    {
        $bon->load([
            'commande.fournisseur',
            'commande.magasinLivraison',
            'commande.details.product',
            'receptions.detail_commande',
        ]);

        $commande    = $bon->commande;
        $fournisseur = $commande?->fournisseur;
        $magasin     = $commande?->magasinLivraison;

        // Construction des lignes du tableau
        $lignes = collect();

        foreach ($commande?->details ?? [] as $detail) {
            $receptionsDetail = $bon->receptions
                ->where('detail_commande_id', $detail->id);

            $totalRecu       = $receptionsDetail->sum('recu');
            $totalInvendable = $receptionsDetail->sum('invendable');

            $lignes->push([
                'ref_interne'      => $detail->ref_interne
                    ?? ($detail->product?->code ?? '—'),
                'designation'      => $detail->product?->designation ?? '—',
                'article'          => $detail->product?->article ?? '—',
                'ref_fournisseur'  => $detail->ref_fournisseur ?? '—',
                'gencode'          => $detail->product?->gencode
                    ?? ($detail->product?->code_barre ?? '—'),
                'geo'              => $detail->product?->geo ?? '—',
                'qte_commandee'    => $detail->quantite ?? 0,
                'qte_recue'        => $totalRecu,
                'cde_grt'          => $detail->cde_grt ?? 0,
                'recu_grt'         => $totalInvendable,
            ]);
        }

        $data = [
            'bon'              => $bon,
            'commande'         => $commande,
            'fournisseur'      => $fournisseur,
            'magasin'          => $magasin,
            'lignes'           => $lignes,
            'date_impression'  => now()->format('d/m/Y'),
            'heure_impression' => now()->format('H:i'),
            'total_articles'   => $lignes->count(),
        ];

        $pdf = Pdf::loadView('pdf.controle-reception', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
                'dpi'                  => 150,
            ]);

        $filename = 'controle-reception-' . ($bon->numero_compte ?? $bon->id) . '.pdf';

        return $pdf->stream($filename);
        // Pour forcer le téléchargement : return $pdf->download($filename);
    }
}
