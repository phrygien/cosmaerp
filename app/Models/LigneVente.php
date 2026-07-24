<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'ligne_vente')]
#[Fillable([
    'vente_id',
    'produit_id',
    'quantite',
    'prix_unitaire',
    'remise',
    'total_ligne'
])]
class LigneVente extends Model
{
    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
