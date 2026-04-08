<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('detail_commande')]
#[Fillable(['pu_achat_HT', 'tax', 'taux_remise', 'pu_achat_net', 'commande_id', 'product_id', 'produit_fournisseur_id', 'quantite', 'state'])]
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

    public function produitFournisseur(): BelongsTo
    {
        return $this->belongsTo(ProduitFournisseur::class, 'produit_fournisseur_id', 'id');
    }
}
