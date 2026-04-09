<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'categorie_code', 'marque_code', 'name', 'state'])]
#[Table('ligne', incrementing: false, key: 'code')]
class Ligne extends Model
{
    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_code');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_code');
    }
}
