<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('reception_commande')]
#[Fillable(['bon_commande_id', 'recu', 'invendable', 'commande_id', 'detail_commande_id', 'state'])]
class ReceptionCommande extends Model
{
    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class, 'bon_commande_id', 'id');
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id', 'id');
    }

    public function detailCommande(): HasOne
    {
        return $this->hasOne(DetailCommande::class, 'detail_commande_id', 'id');
    }
}
