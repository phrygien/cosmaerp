<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'session_caisse')]
#[Fillable([
    'caisse_id',
    'user_id',
    'solde_ouverture',
    'solde_fermeture',
    'opened_at',
    'closed_at',
    'status' // ouverte, fermee
])]
class SessionCaisse extends Model
{
    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementCaisse::class);
    }
}
