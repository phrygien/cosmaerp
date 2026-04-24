<?php

namespace App\Enums;

enum EtatusReception: string
{
    case Open = "Ouvert";
    case Closed = "Termine";

    public function lable(): string
    {
        return match($this) {
            self::Open => "Open",
            self::Closed => "Closed",
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Open => "orange",
            self::Closed => "green",
        };
    }

}
