<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'caisse')]
#[Fillable([
    'magasin_id',
    'name',
    'code',
    'status', // ouverte, fermee
    'solde_actuel'
])]
class Caisse extends Model
{
    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SessionCaisse::class);
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }
}
