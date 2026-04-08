<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'state'])]
#[Table(name: 'marque', incrementing: true)]
class Marque extends Model
{
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
