<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'mouvement_caisse')]
#[Fillable([
    'session_caisse_id',
    'user_id',
    'type', // entree, sortie
    'montant',
    'motif'
])]
class MouvementCaisse extends Model
{
    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionCaisse::class, 'session_caisse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
