<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('commande')]
#[Fillable(['remise_facture', 'montant_minimum', 'nombre_jour', 'fournisseur_id', 'magasin_livraison_id', 'libelle', 'montant_total', 'status', 'state', 'etat'])]
class Commande extends Model
{
    public function magasinLivraison(): HasOne
    {
        return $this->hasOne(Magasin::class, 'id', 'magasin_livraison_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id', 'id');
    }
}
