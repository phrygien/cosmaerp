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
}
