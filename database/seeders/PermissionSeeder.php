<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Utilisateurs
            [
                "name" => "Voir les utilisateurs",
                "slug" => "users.index",
                "group" => "Utilisateurs",
            ],
            [
                "name" => "Créer un utilisateur",
                "slug" => "users.create",
                "group" => "Utilisateurs",
            ],
            [
                "name" => "Modifier un utilisateur",
                "slug" => "users.edit",
                "group" => "Utilisateurs",
            ],
            [
                "name" => "Supprimer un utilisateur",
                "slug" => "users.delete",
                "group" => "Utilisateurs",
            ],

            // Rôles
            [
                "name" => "Voir les rôles",
                "slug" => "roles.index",
                "group" => "Rôles",
            ],
            [
                "name" => "Créer un rôle",
                "slug" => "roles.create",
                "group" => "Rôles",
            ],
            [
                "name" => "Modifier un rôle",
                "slug" => "roles.edit",
                "group" => "Rôles",
            ],
            [
                "name" => "Supprimer un rôle",
                "slug" => "roles.delete",
                "group" => "Rôles",
            ],

            // Permissions
            [
                "name" => "Voir les permissions",
                "slug" => "permissions.index",
                "group" => "Permissions",
            ],
            [
                "name" => "Créer une permission",
                "slug" => "permissions.create",
                "group" => "Permissions",
            ],
            [
                "name" => "Modifier une permission",
                "slug" => "permissions.edit",
                "group" => "Permissions",
            ],
            [
                "name" => "Supprimer une permission",
                "slug" => "permissions.delete",
                "group" => "Permissions",
            ],

            // Tableau de bord
            [
                "name" => "Voir le dashboard",
                "slug" => "dashboard.index",
                "group" => "Dashboard",
            ],
            [
                "name" => "Voir les statistiques",
                "slug" => "dashboard.stats",
                "group" => "Dashboard",
            ],

            // Paramètres
            [
                "name" => "Voir les paramètres",
                "slug" => "settings.index",
                "group" => "Paramètres",
            ],
            [
                "name" => "Modifier les paramètres",
                "slug" => "settings.edit",
                "group" => "Paramètres",
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ["slug" => $permission["slug"]],
                $permission,
            );
        }
    }
}
