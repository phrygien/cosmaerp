<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('historique_quantite_detail_commande')]
#[Fillable([
    'detail_commande_id',
    'commande_id',
    'product_id',
    'ancienne_quantite',
    'nouvelle_quantite',
    'motif',
    'user_id',
])]
class HistoriqueQuantiteDetailCommande extends Model
{
    public function detailCommande(): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class, 'detail_commande_id');
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
