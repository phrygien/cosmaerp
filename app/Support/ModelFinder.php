<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class ModelFinder
{
    /**
     * Retourne la liste des FQCN de tous les modèles Eloquent
     * concrets présents dans app/Models (récursif).
     *
     * @return array<int, class-string<Model>>
     */
    public static function all(): array
    {
        $path      = app_path('Models');
        $namespace = app()->getNamespace(); // "App\"

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::allFiles($path))
            ->map(function ($file) use ($namespace) {
                $relative = Str::after($file->getPathname(), app_path() . DIRECTORY_SEPARATOR);

                return $namespace . str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        $relative
                    );
            })
            ->filter(function (string $class) {
                if (! class_exists($class)) {
                    return false;
                }

                $reflection = new ReflectionClass($class);

                return $reflection->isSubclassOf(Model::class)
                    && ! $reflection->isAbstract()
                    && ! $reflection->isSubclassOf(Pivot::class);
            })
            ->values()
            ->all();
    }

    /**
     * Retourne les noms courts (ex: "User", "Permission") triés.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return collect(self::all())
            ->map(fn (string $class) => class_basename($class))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
