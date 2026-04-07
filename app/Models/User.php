<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(["name", "email", "password", "status"])]
#[
    Hidden([
        "password",
        "two_factor_secret",
        "two_factor_recovery_codes",
        "remember_token",
    ]),
]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(" ")
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode("");
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, "user_roles");
    }

    public function hasRole(string|array $slug): bool
    {
        $slugs = (array) $slug;
        return $this->roles()->whereIn("slug", $slugs)->exists();
    }

    public function hasAllRoles(array $slugs): bool
    {
        return $this->roles()->whereIn("slug", $slugs)->count() ===
            count($slugs);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles()
            ->whereHas("permissions", fn($q) => $q->whereIn("slug", $slugs))
            ->exists();
    }

    public function hasAnyPermission(array $slugs): bool
    {
        return $this->roles()
            ->whereHas("permissions", fn($q) => $q->whereIn("slug", $slugs))
            ->exists();
    }

    public function getAllPermission(): \Illuminate\Support\Collection
    {
        return $this->roles->flatMap->permissions->unique("id")->values();
    }

    public function assignRole(string|Role ...$roles): void
    {
        $ids = collect($roles)
            ->map(
                fn($r) => $r instanceof Role
                    ? $r->id
                    : Role::where("slug", $r)->value("id"),
            )
            ->filter();

        $this->roles()->syncWithoutDetaching($ids);
    }

    public function removeRole(string|Role $role): void
    {
        $id =
            $role instanceof Role
                ? $role->id
                : Role::where("slug", $role)->value("id");

        $this->roles()->detach($id);
    }

    public function syncRoles(array $slugs): void
    {
        $ids = Role::whereIn("slug", $slugs)->pluck("id");

        $this->roles()->sync($ids);
    }
}
