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
    Route::livewire("/fournisseurs", "pages::fournisseurs.page")->name("fournisseurs");
    Route::livewire("/fournisseurs/{fournisseur}", "pages::fournisseurs.view")->name("fournisseurs.view");

    // Magasin
    Route::livewire("/magasin", "pages::magasin.page")->name("magasin");

});

Route::group(["middleware" => ["auth", "verified"], "prefix" => 'catalogue'], function () {
    Route::livewire('/marques', "pages::marques.page")->name("catalogue.marques");
    Route::livewire('/categories', "pages::categories.page")->name("catalogue.categories");
    Route::livewire('/parkod', "pages::parkod.page")->name("catalogue.parkod");
    Route::livewire('/products', 'pages::products.page')->name("catalogue.products");
});

Route::group(["middleware" => ["auth", "verified"], "prefix" => 'orders'], function () {
    Route::livewire('/list', "pages::orders.page")->name("orders.list");
    Route::livewire('/create', "pages::orders.create")->name("orders.create");
});

require __DIR__ . "/settings.php";
