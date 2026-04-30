<?php

namespace App\Enums;

enum TypeFacture: string
{
    case Commande = 'Commande';

    case Retour = 'Retour';

    public function label(): string
    {
        return match ($this) {
            self::Commande => 'Facture',
            self::Retour => 'Retour',
        };
    }
}
