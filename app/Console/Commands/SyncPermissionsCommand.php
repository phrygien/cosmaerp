<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Support\ModelFinder;
use App\Support\Permissions;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = "Génère automatiquement les permissions (Any/View/Create/Delete) pour chaque modèle du projet";

    public function handle(): int
    {
        $models  = ModelFinder::names();
        $created = 0;

        foreach ($models as $model) {
            foreach (array_keys(Permissions::ABILITIES) as $ability) {
                $permission = Permission::firstOrCreate(
                    ['slug' => Permissions::slug($ability, $model)],
                    [
                        'name'  => Permissions::name($ability, $model),
                        'group' => Permissions::group($model),
                    ]
                );

                if ($permission->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        $this->info("{$created} permission(s) créée(s) pour " . count($models) . " modèle(s) trouvé(s).");

        return self::SUCCESS;
    }
}
