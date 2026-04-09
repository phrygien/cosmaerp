<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'state'])]
#[Table(name: 'marque', incrementing: false, key: 'code')]
class Marque extends Model
{
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('state', 1);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('state', 0);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(fn($q) =>
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%")
        );
    }

    public function scopeByState(Builder $query, ?string $state): Builder
    {
        if ($state === '1') return $query->active();
        if ($state === '0') return $query->inactive();
        return $query;
    }

    public function isActive(): bool
    {
        return $this->state == 1;
    }
}
