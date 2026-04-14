<?php

namespace App\Http\Controllers;

use App\Models\BonCommande;
use App\Models\Commande;
use Barryvdh\DomPDF\Facade\Pdf;

class BonCommandePdfController extends Controller
{
    public function download(int $id)
    {
        $commande = Commande::with([
            'fournisseur',
            'magasinLivraison',
            'details.product',
            'details.destinations.magasin',
        ])->findOrFail($id);

        $bonCommande = BonCommande::with([
            'magasinFacturation',
            'magasinLivraison',
        ])->where('commande_id', $id)->first();

        $pdf = Pdf::loadView('pdf.bon-commande', compact('commande', 'bonCommande'))
            ->setPaper('a4');

        return $pdf->download('bon-commande-' . $id . '.pdf');
    }
}
