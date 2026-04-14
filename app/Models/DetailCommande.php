<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('detail_commande')]
#[Fillable(['pu_achat_HT', 'tax', 'taux_remise', 'pu_achat_net', 'commande_id', 'product_id', 'quantite', 'state'])]
class DetailCommande extends Model
{
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(DestinationDetailCommande::class, 'detail_commande_id', 'id');
    }
}
