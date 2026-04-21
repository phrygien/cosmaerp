<?php

namespace App\Enums;

enum BaseStore: int
{
    case BaseStock = 1;
    case NotBaseStock = 0;

    public function label(): string
    {
        return match ($this) {
            self::BaseStock => __('Base Stock'),
            self::NotBaseStock => __('Not Base Stock'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BaseStock => 'green',
            self::NotBaseStock => 'purple',
        };
    }
}
