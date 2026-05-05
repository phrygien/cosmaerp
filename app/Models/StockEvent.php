<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'stock_events')]
#[Fillable([
    'event_id',
    'sku',
    'delta',
    'source'
])]
class StockEvent extends Model
{
   
}
