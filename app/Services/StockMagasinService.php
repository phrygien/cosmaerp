<?php

namespace App\Services;

use App\Models\StockMagasin;
use App\Models\Product;

class StockMagasinService
{
    public function applyDelta($sku, $delta)
    {
        $product = Product::where('EAN', $sku)->first();

        if(!$product) return;

        $magasinId = (int) env('STOCK_MAGASIN_ID');

        // Delta positif = remise en stock → on remet dans le lot le plus ancien non vide
        // sinon on remet dans le lot à zéro le plus ancien
        if ($delta > 0) {
            $lot = StockMagasin::where('product_id', $product->id)
                ->where('magasin_id', $magasinId)
                ->where('nb_item', '>', 0)
                ->orderBy('deposite_date', 'asc')
                ->first();

            // Aucun lot non vide → on prend le lot à zéro le plus ancien
            if (!$lot) {
                $lot = StockMagasin::where('product_id', $product->id)
                    ->where('magasin_id', $magasinId)
                    ->where('nb_item', 0)
                    ->orderBy('deposite_date', 'asc')
                    ->first();
            }

            if (!$lot) return;

            $lot->nb_item += $delta;
            $lot->save();

            return StockMagasin::where('product_id', $product->id)
            ->where('magasin_id', $magasinId)
            ->sum('nb_item');
        }

        // Delta négatif = sortie → FIFO : lots les plus anciens non vides d'abord
        $remaining = abs($delta);

        $lots = StockMagasin::where('product_id', $product->id)
            ->where('magasin_id', $magasinId)
            ->where('nb_item', '>', 0)
            ->orderBy('deposite_date', 'asc')
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0) break;

            if ($lot->nb_item <= $remaining) {
                $remaining    -= $lot->nb_item;
                $lot->nb_item  = 0;
                $lot->save();
            } else {
                $lot->nb_item -= $remaining;
                $remaining     = 0;
                $lot->save();
            }
        }

        return StockMagasin::where('product_id', $product->id)
            ->where('magasin_id', $magasinId)
            ->sum('nb_item');
    }
}
