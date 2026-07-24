<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'marque_code', 'state'])]
#[Table('categorie', incrementing: false, key: 'code')]
class Category extends Model
{
    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_code');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_code');
    }
}
