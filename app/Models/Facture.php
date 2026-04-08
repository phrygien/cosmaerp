<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'facture')]
#[Fillable(['fournisseur_id', 'type', 'libelle', 'numero', 'date_commande', 'montant', 'date_reception', 'commande_id', 'remise', 'tax', 'state'])]
class Facture extends Model
{
    public function forfaisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id', 'id');
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id', 'id');
    }
}
