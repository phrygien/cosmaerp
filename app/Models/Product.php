<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_code', 'marque_code', 'categorie_code', 'ligne_code', 'type_id', 'designation_variant', 'article', 'ref_fabri_n_1', 'EAN', 'pght_parkod', 'tva', 'devise', 'hs_code', 'statut_parkod', 'state'])]
#[Table('product')]
class Product extends Model
{
    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_code');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_code');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class, 'ligne_code');
    }
}
