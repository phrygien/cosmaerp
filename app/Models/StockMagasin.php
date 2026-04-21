<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'stock_magasin')]
#[Fillable([
    'nb_item',
    'deposite_date',
    'magasin_id',
    'product_id',
    'gen_code',
    'detail_commande_id',
    'state'
])]
class StockMagasin extends Model
{
    public  function magasin (): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id', 'id');
    }

    public  function product (): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function detailCommande (): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class, 'detail_commande_id', 'id');
    }

    /**
     * Génère un code unique basé sur le magasin, le produit et la date.
     * Format : MAG{magasin_id}-PROD{product_id}-{YYYYMMDD}
     * Exemple : MAG01-PROD05-20250421
     */
    public static function generateGenCode(int $magasinId, int $productId, ?\DateTimeInterface $date = null): string
    {
        $date    = $date ?? now();
        $base    = sprintf('MAG%02d-PROD%02d-%s', $magasinId, $productId, $date->format('Ymd'));
        $suffix  = 1;
        $genCode = $base;

        // Garantir l'unicité en ajoutant un suffixe si nécessaire
        while (static::where('gen_code', $genCode)->exists()) {
            $genCode = $base . '-' . $suffix;
            $suffix++;
        }

        return $genCode;
    }
}
