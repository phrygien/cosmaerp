<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BonCommandePdfController;

// Route publique
Route::view("/", "welcome")->name("home");

// Middleware partagé — évite la répétition et permet le cache de route
Route::middleware(["auth", "verified"])->group(function () {

    // Dashboard
    Route::view("dashboard", "dashboard")->name("dashboard");

    // Admin / Utilisateurs
    Route::livewire("/roles", "pages::roles.page")->name("roles");
    Route::livewire("/permissions", "pages::permissions.page")->name("permissions");
    Route::livewire("/users", "pages::users.page")->name("users");

    // Fournisseurs
    Route::livewire("/fournisseurs", "pages::fournisseurs.page")->name("fournisseurs");
    Route::livewire("/fournisseurs/{fournisseur}", "pages::fournisseurs.view")->name("fournisseurs.view");

    // Magasin
    Route::livewire("/magasin", "pages::magasin.page")->name("magasin");

    // Catalogue
    Route::prefix("catalogue")->name("catalogue.")->group(function () {
        Route::livewire("/marques", "pages::marques.page")->name("marques");
        Route::livewire("/categories", "pages::categories.page")->name("categories");
        Route::livewire("/parkod", "pages::parkod.page")->name("parkod");
        Route::livewire("/products", "pages::products.page")->name("products");
    });

    // Commandes
    Route::prefix("orders")->name("orders.")->group(function () {
        Route::livewire("/list", "pages::orders.page")->name("list");
        Route::livewire("/create", "pages::orders.create")->name("create");
        Route::livewire("/edit/{commande_id}", "pages::orders.edit")->name("edit");
    });

    // Réception / Approvisionnement
    Route::prefix("reception")->name("reception_commande.")->group(function () {
        Route::livewire("/list", "pages::aprovisionement.page")->name("list");
        Route::livewire("/create", "pages::aprovisionement.reception.create")->name("create");
    });

    // PDF Bon de commande
    Route::get("/commandes/{id}/bon-commande/pdf", [BonCommandePdfController::class, "download"])
        ->name("bon-commande.pdf");
});

require __DIR__ . "/settings.php";
