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


Route::group(["middleware" => ["auth", "verified"], "prefix" => 'catalogue'], function () {
    Route::livewire('/marques', "pages::marques.page")->name("catalogue.marques");
    Route::livewire('/categories', "pages::categories.page")->name("catalogue.categories");
    Route::livewire('/parkod', "pages::parkod.page")->name("catalogue.parkod");
});

require __DIR__ . "/settings.php";
