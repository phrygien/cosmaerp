<?php

namespace App\Models;

use App\Enums\CommandeEtat;
use App\Enums\CommandeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('commande')]
#[Fillable(['remise_facture', 'montant_minimum', 'nombre_jour', 'fournisseur_id', 'magasin_livraison_id', 'libelle', 'montant_total', 'status', 'state', 'etat'])]
class Commande extends Model
{
    protected function casts(): array
    {
        return [
            'status' => CommandeStatus::class,
            'etat' => CommandeEtat::class,
            'date_facturation' => 'datetime',
            'date_cloture'     => 'datetime',
            'date_annulation'  => 'datetime',
            'date_reception'   => 'datetime',
        ];
    }

    public function magasinLivraison(): HasOne
    {
        return $this->hasOne(Magasin::class, 'id', 'magasin_livraison_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailCommande::class, 'commande_id');
    }

    public function detailsCommande(): HasMany
    {
        return $this->hasMany(DetailCommande::class, 'commande_id', 'id');
    }
}
