<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'magasin')]
#[Fillable([
    'name',
    'state',
    'type',
    'store_url',
    'adress',
    'telephone',
    'email'
])]
class Magasin extends Model
{

}
