<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\ModelFinder;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. S'assurer que toutes les permissions (Any/View/Create/Delete
        //    pour chaque modèle du projet) existent en base.
        foreach (ModelFinder::names() as $model) {
            foreach (array_keys(Permissions::ABILITIES) as $ability) {
                Permission::firstOrCreate(
                    ['slug' => Permissions::slug($ability, $model)],
                    [
                        'name'  => Permissions::name($ability, $model),
                        'group' => Permissions::group($model),
                    ]
                );
            }
        }

        // 2. Créer (ou récupérer) le rôle Super Admin.
        //    ⚠️ Le slug "super-admin" doit correspondre à celui utilisé
        //    dans le bypass de App\Models\User::hasPermission().
        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name'        => 'Super Admin',
                'description' => 'Accès complet à toutes les fonctionnalités du système.',
            ]
        );

        // 3. Lui attribuer toutes les permissions existantes (utile pour
        //    l'affichage dans l'UI, même si le bypass rend ça redondant
        //    pour le contrôle d'accès lui-même).
        $role->permissions()->sync(Permission::pluck('id'));

        $this->command?->info(
            "Rôle 'Super Admin' prêt avec " . Permission::count() . " permission(s)."
        );

        // 4. (Optionnel) Créer/assigner un utilisateur Super Admin.
        //    Définis SUPER_ADMIN_EMAIL (et éventuellement SUPER_ADMIN_PASSWORD)
        //    dans ton .env pour activer cette étape.
        if ($email = env('SUPER_ADMIN_EMAIL')) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => env('SUPER_ADMIN_NAME', 'Super Admin'),
                    'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
                ]
            );

            $user->assignRole($role);

            $this->command?->info("Utilisateur Super Admin : {$email}");
        }
    }
}
