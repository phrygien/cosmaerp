<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'Super Admin',
                'slug'        => 'super-admin',
                'description' => 'Accès complet à toutes les fonctionnalités.',
            ],
            [
                'name'        => 'Administrateur',
                'slug'        => 'admin',
                'description' => 'Gestion des utilisateurs et des paramètres.',
            ],
            [
                'name'        => 'Modérateur',
                'slug'        => 'moderateur',
                'description' => 'Modération du contenu et des utilisateurs.',
            ],
            [
                'name'        => 'Éditeur',
                'slug'        => 'editeur',
                'description' => 'Création et modification du contenu.',
            ],
            [
                'name'        => 'Utilisateur',
                'slug'        => 'utilisateur',
                'description' => 'Accès basique à la plateforme.',
            ],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        // Attacher toutes les permissions au Super Admin
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $superAdmin->permissions()->sync(Permission::pluck('id'));

        // Attacher certaines permissions à l'Admin
        $admin = Role::where('slug', 'admin')->first();
        $admin->permissions()->sync(
            Permission::whereIn('group', ['Utilisateurs', 'Rôles', 'Dashboard'])
                ->pluck('id')
        );

        // Attacher permissions dashboard à l'Éditeur
        $editeur = Role::where('slug', 'editeur')->first();
        $editeur->permissions()->sync(
            Permission::where('group', 'Dashboard')->pluck('id')
        );
    }
}
