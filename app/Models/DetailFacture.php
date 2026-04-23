<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('detail_facture')]
#[Fillable('quantite_commande', 'montant_HT', 'montant_remise', 'montant_final_ht', 'montant_final_net', 'facture_id', 'detail_commande_id', 'state')]
class DetailFacture extends Model
{
    public function facture (): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function detailCommande (): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class, 'detail_commande_id');
    }
}
