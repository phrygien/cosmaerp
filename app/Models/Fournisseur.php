<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('fournisseur')]
#[Fillable(['name', 'date_creation', 'code', 'raison_social', 'adresse_siege', 'code_postal', 'ville', 'telephone', 'fax', 'mail', 'adresse_retour', 'code_postal_retour', 'ville_retour', 'state'])]
class Fournisseur extends Model
{
    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'fournisseur_id', 'id');
    }
}
