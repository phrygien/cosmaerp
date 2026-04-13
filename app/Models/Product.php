<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

#[Fillable([
    'product_code',
    'marque_code',
    'categorie_code',
    'ligne_code',
    'type_id',
    'designation',
    'designation_variant',
    'article',
    'ref_fabri_n_1',
    'EAN',
    'pght_parkod',
    'tva',
    'devise',
    'hs_code',
    'statut_parkod',
    'state'
])]
#[Table('product')]
class Product extends Model
{
    use Searchable;
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

    // app/Models/Product.php

    public function getScoutKey(): string
    {
        return (string) $this->getKey();
    }

    public function getScoutKeyName(): string
    {
        return 'id';
    }

    public function toSearchableArray(): array
    {
        return [
            'id'                  => (string) $this->id,  // ← bien en string
            'product_code'        => $this->product_code        ?? '',
            'marque_code'         => $this->marque_code         ?? '',
            'marque_nom'          => $this->marque?->name        ?? '',
            'categorie_code'      => $this->categorie_code      ?? '',
            'categorie_nom'       => $this->categorie?->name     ?? '',
            'ligne_code'          => $this->ligne_code          ?? '',
            'ligne_nom'           => $this->ligne?->name         ?? '',
            'type_id'             => (int) ($this->type_id      ?? 0),
            'type_nom'            => $this->type?->name          ?? '',
            'designation'         => $this->designation         ?? '',
            'designation_variant' => $this->designation_variant ?? '',
            'article'             => $this->article             ?? '',
            'ref_fabri_n_1'       => $this->ref_fabri_n_1       ?? '',
            'EAN'                 => $this->EAN                 ?? '',
            'pght_parkod'         => (float) ($this->pght_parkod ?? 0),
            'tva'                 => (float) ($this->tva         ?? 0),
            'devise'              => $this->devise               ?? '',
            'hs_code'             => $this->hs_code             ?? '',
            'statut_parkod'       => $this->statut_parkod       ?? '',
            'state'               => (int) ($this->state        ?? 0),
            'created_at'          => $this->created_at?->timestamp ?? time(),
            'updated_at'          => $this->updated_at?->timestamp ?? time(),
        ];
    }
}
