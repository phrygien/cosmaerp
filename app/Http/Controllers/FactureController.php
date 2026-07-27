<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Magasin;
use Illuminate\Http\Request;
use LaravelDaily\Invoices\Invoice;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Classes\InvoiceItem;

class FactureController extends Controller
{
    /**
     * Route :
     *   Route::get('/facture/pdf/{facture}', FactureController::class)
     *       ->name('facture.pdf');
     *
     * Nécessite : composer require laraveldaily/laravel-invoices
     *             php artisan invoices:install
     */
    public function __invoke(Request $request, Facture $facture)
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

        // Magasin émetteur (base_stock en priorité)
        $magasinEmetteur = Magasin::where('base_stock', true)->first() ?? $magasin;

        // ── Vendeur (seller) ────────────────────────────────────────────
        $seller = new Party([
            'name'          => $magasinEmetteur?->nom ?? 'Ma Société',
            'address'       => $magasinEmetteur?->adresse ?? '',
            'code'          => $magasinEmetteur?->code_fiscal ?? '',
            'phone'         => $magasinEmetteur?->telephone ?? '',
            'custom_fields' => [
                'RC'  => $magasinEmetteur?->registre_commerce ?? '',
                'NIF' => $magasinEmetteur?->nif ?? '',
            ],
        ]);

        // ── Acheteur (buyer) ────────────────────────────────────────────
        $buyer = new Party([
            'name'          => $fournisseur?->nom ?? '—',
            'address'       => $fournisseur?->adresse ?? '',
            'code'          => $fournisseur?->code_fiscal ?? '',
            'custom_fields' => [
                'N° commande' => $commande?->numero ?? '',
            ],
        ]);

        // ── Lignes de facture ────────────────────────────────────────────
        $items = collect();

        foreach ($facture->detailsFacture as $df) {
            $dc      = $df->detailCommande;
            $product = $dc?->product;

            $qte  = (float) ($df->quantite_commande ?? 0);
            $puHT = (float) ($dc?->pu_achat_HT
                ?? ($qte > 0 ? $df->montant_HT / $qte : 0));

            $item = InvoiceItem::make($product?->designation ?? '—')
                ->description($product?->article ?? '')
                ->pricePerUnit($puHT)
                ->quantity($qte ?: 1);

            // Remise en montant (le package accepte un montant fixe OU un %)
            if (!empty($df->montant_remise)) {
                $item->discount((float) $df->montant_remise);
            } elseif (!empty($dc?->taux_remise)) {
                $item->discountByPercent((float) $dc->taux_remise);
            }

            // TVA par ligne, si le package/la version le permet
            if (!empty($dc?->tax)) {
                $item->taxByPercent((float) $dc->tax);
            }

            // On force le sous-total net si déjà calculé côté métier
            if (!empty($df->montant_final_net)) {
                $item->subTotalPrice((float) $df->montant_final_net);
            }

            $items->push($item);
        }

        // ── Construction de la facture ───────────────────────────────────
        $invoice = Invoice::make('facture')
            ->series($facture->serie ?? 'FA')
            ->sequence($facture->numero_sequence ?? $facture->id)
            ->serialNumberFormat('{SEQUENCE}/{SERIES}')
            ->seller($seller)
            ->buyer($buyer)
            ->date($facture->date_facture ?? $facture->created_at)
            ->dateFormat('d/m/Y')
            ->payUntilDays(0)
            ->currencySymbol('CFA') // adapter selon votre devise (ex: '€', '$', 'MRU')
            ->currencyCode($facture->devise ?? 'XOF')
            ->currencyFormat('{VALUE} {SYMBOL}')
            ->currencyThousandsSeparator(' ')
            ->currencyDecimalPoint(',')
            ->filename('facture-' . ($facture->numero ?? $facture->id))
            ->addItems($items);

        // Statut payé / dû si l'info existe sur le modèle
        if (isset($facture->statut)) {
            $invoice->status($facture->statut === 'paye'
                ? __('invoices::invoice.paid')
                : __('invoices::invoice.unpaid'));
        }

        // Logo : le package attend un CHEMIN LOCAL (il fait un file_get_contents
        // + base64_encode en interne), donc on télécharge l'image distante une
        // seule fois puis on la met en cache localement.
        $logoPath = $this->resolveLogo(
            'https://images.prismic.io/wanteeed/cosma-parfumeries.jpeg?auto=compress,format&rect=0,0,200,200&w=400&h=400'
        );

        if ($logoPath) {
            $invoice->logo($logoPath);
        }

        // Notes
        if (!empty($facture->notes)) {
            $invoice->notes($facture->notes);
        }

        // Totaux forcés depuis vos données déjà calculées (le package les
        // recalculera automatiquement si vous les omettez)
        $invoice->totalDiscount($facture->detailsFacture->sum('montant_remise'))
            ->totalAmount($facture->montant ?? $facture->detailsFacture->sum('montant_final_net'));

        // Sauvegarde optionnelle sur disque (ex: 'public')
        // $invoice->save('public');
        // $url = $invoice->url();

        return $invoice->stream(); // ou ->download()
    }

    /**
     * Télécharge une image distante (logo) et la met en cache localement,
     * car LaravelDaily\Invoices\Invoice::logo() attend un chemin de fichier
     * local (il fait un file_get_contents + base64_encode en interne et ne
     * sait donc pas lire directement une URL http).
     *
     * @return string|null chemin local absolu, ou null si échec
     */
    private function resolveLogo(string $url): ?string
    {
        $cacheDir = storage_path('app/invoices-logos');

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cachePath = $cacheDir . '/' . md5($url) . '.jpg';

        // Déjà téléchargé récemment (cache 1 jour) → on réutilise
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 86400) {
            return $cachePath;
        }

        try {
            $contents = file_get_contents($url);

            if ($contents === false) {
                return file_exists($cachePath) ? $cachePath : null;
            }

            file_put_contents($cachePath, $contents);

            return $cachePath;
        } catch (\Throwable $e) {
            report($e);

            // En cas d'échec réseau, on retombe sur une version déjà en cache si elle existe
            return file_exists($cachePath) ? $cachePath : null;
        }
    }
}
