<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table(name: 'bon_commande')]
#[Fillable(['commande_id', 'code_fournisseur', 'numero_compte', 'date_commande', 'date_livraison_prevue', 'magasin_facturation_id', 'magasin_livraison_id', 'montant_commande_net', 'state'])]
class BonCommande extends Model
{
    public function commande(): HasOne
    {
        return $this->hasOne(Commande::class, 'id', 'commande_id');
    }

    public function magasinFacturation(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_facturation_id', 'id');
    }

    public function magasinLivraison(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_livraison_id', 'id');
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(ReceptionCommande::class, 'bon_commande_id');
    }
}
