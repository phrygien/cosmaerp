<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'state'])]
#[Table(name: 'type')]
class Type extends Model
{
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'type_id');
    }
}
