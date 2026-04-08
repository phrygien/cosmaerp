<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('representant_fournisseur')]
#[Fillable([
    'full_name',
    'adresse',
    'code_postal',
    'ville',
    'telephone',
    'fax',
    'telephone_portable',
    'mail',
    'full_name_assistant',
    'telephone_assistant',
    'fax_assistant',
    'mail_assistant',
    'fournisseur_id',
    'state'
])]
class RepresentantFournisseur extends Model
{
    public function fournisseur (): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id', 'id');
    }
}
