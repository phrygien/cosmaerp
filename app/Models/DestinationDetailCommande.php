<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'magasin_id',
    'quantite',
    'detail_commande_id',
    'state'
])]
class DestinationDetailCommande extends Model
{
    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function detailCommande(): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class, 'detail_commande_id');
    }
}
