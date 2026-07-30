<?php

namespace App\Support;

use Illuminate\Support\Str;

class Permissions
{
    public const VIEW_ANY = 'view_any';
    public const VIEW     = 'view';
    public const CREATE   = 'create';
    public const DELETE   = 'delete';

    /**
     * Capacités disponibles, avec leur libellé (utilisé dans la matrice
     * du formulaire de rôle et dans le nom lisible de la permission).
     */
    public const ABILITIES = [
        self::VIEW_ANY => 'Any',
        self::VIEW     => 'View',
        self::CREATE   => 'Create',
        self::DELETE   => 'Delete',
    ];

    /**
     * Construit le slug d'une permission : "{ability}_{model_snake}".
     * Accepte le nom court du modèle ("User") ou le FQCN (User::class).
     */
    public static function slug(string $ability, string $model): string
    {
        return $ability . '_' . Str::snake(class_basename($model));
    }

    /**
     * Nom lisible, ex: "Any User", "Create Post".
     */
    public static function name(string $ability, string $model): string
    {
        return (self::ABILITIES[$ability] ?? $ability) . ' ' . class_basename($model);
    }

    /**
     * Groupe (= nom du modèle), utilisé pour filtrer la liste des permissions.
     */
    public static function group(string $model): string
    {
        return class_basename($model);
    }

    public static function viewAny(string $model): string
    {
        return self::slug(self::VIEW_ANY, $model);
    }

    public static function view(string $model): string
    {
        return self::slug(self::VIEW, $model);
    }

    public static function create(string $model): string
    {
        return self::slug(self::CREATE, $model);
    }

    public static function delete(string $model): string
    {
        return self::slug(self::DELETE, $model);
    }
}
