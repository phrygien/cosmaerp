<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'historique_product')]
#[Fillable([
    'mouvement',
    'descriptif',
    'product_id'
])]
class HistoriqueProduct extends Model
{
    public function product (): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
