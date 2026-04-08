<?php

use Illuminate\Support\Facades\Route;

Route::view("/", "welcome")->name("home");

Route::middleware(["auth", "verified"])->group(function () {
    Route::view("dashboard", "dashboard")->name("dashboard");

    Route::livewire("/roles", "pages::roles.page")->name("roles");
    Route::livewire("/permissions", "pages::permissions.page")->name(
        "permissions",
    );
    Route::livewire("/users", "pages::users.page")->name("users");
});

require __DIR__ . "/settings.php";
