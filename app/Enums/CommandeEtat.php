<?php

namespace App\Enums;

enum CommandeEtat: string
{
    case PreCommande = 'pre_commande';
    case Commande = 'commande';

    public function label(): string
    {
        return match ($this) {
            self::PreCommande => __('Précommande'),
            self::Commande => __('Commande'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PreCommande => 'blue',
            self::Commande => 'purple',
        };
    }
}
