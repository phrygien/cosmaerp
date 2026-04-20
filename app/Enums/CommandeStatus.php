<?php

namespace App\Enums;

enum CommandeStatus: int
{
    case Annulee  = -1;
    case Cree     = 1;
    case Facturee = 2;
    case Cloturee = 3;

    public function label(): string
    {
        return match($this) {
            self::Annulee  => 'Annulée',
            self::Cree     => 'Créée',
            self::Facturee => 'Facturée',
            self::Cloturee => 'Clôturée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Annulee  => 'red',
            self::Cree     => 'blue',
            self::Facturee => 'amber',
            self::Cloturee => 'green',
        };
    }
}
