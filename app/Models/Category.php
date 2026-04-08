<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'name', 'marque_code', 'state'])]
#[Table('categorie', incrementing: false)]
class Category extends Model
{
    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_code');
    }
}
