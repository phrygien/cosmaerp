<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BonCommandePdfController;
use App\Support\Permissions;

// Modèles utilisés pour générer les slugs de permission.
// ⚠️ Ajuste les noms/namespaces ci-dessous pour qu'ils correspondent
//    exactement à tes classes dans app/Models.
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Fournisseur;
use App\Models\Magasin;
use App\Models\Marque;
use App\Models\Categorie;
use App\Models\Parkod;
use App\Models\Product;
use App\Models\Commande;
use App\Models\ReceptionCommande;
use App\Models\Facture;

// Route publique
Route::view("/", "welcome")->name("home");

// Middleware partagé — évite la répétition et permet le cache de route
Route::middleware(["auth", "verified", \App\Http\Middleware\TrackLastSee::class])->group(function () {

    // Dashboard
    Route::view("dashboard", "dashboard")->name("dashboard");

    // Admin / Utilisateurs
    Route::livewire("/roles", "pages::roles.page")
        ->middleware("permission:" . Permissions::viewAny(Role::class))
        ->name("roles");

    Route::livewire("/permissions", "pages::permissions.page")
        ->middleware("permission:" . Permissions::viewAny(Permission::class))
        ->name("permissions");

    Route::livewire("/users", "pages::users.page")
        ->middleware("permission:" . Permissions::viewAny(User::class))
        ->name("users");

    // Fournisseurs
    Route::livewire("/fournisseurs", "pages::fournisseurs.page")
        ->middleware("permission:" . Permissions::viewAny(Fournisseur::class))
        ->name("fournisseurs");

    Route::livewire("/fournisseurs/{fournisseur}", "pages::fournisseurs.view")
        ->middleware("permission:" . Permissions::view(Fournisseur::class))
        ->name("fournisseurs.view");

    // Magasin
    Route::livewire("/magasin", "pages::magasin.page")
        ->middleware("permission:" . Permissions::viewAny(Magasin::class))
        ->name("magasin");

    Route::livewire("/magasin/{id}", "pages::magasin.stock")
        ->middleware("permission:" . Permissions::view(Magasin::class))
        ->name("magasin.view");

    // Catalogue
    Route::prefix("catalogue")->name("catalogue.")->group(function () {
        Route::livewire("/marques", "pages::marques.page")
            ->middleware("permission:" . Permissions::viewAny(Marque::class))
            ->name("marques");

        Route::livewire("/categories", "pages::categories.page")
            ->middleware("permission:" . Permissions::viewAny(Categorie::class))
            ->name("categories");

        Route::livewire("/parkod", "pages::parkod.page")
            ->middleware("permission:" . Permissions::viewAny(Product::class))
            ->name("parkod");

        Route::livewire("/products", "pages::products.page")
            ->middleware("permission:" . Permissions::viewAny(Product::class))
            ->name("products");

        // Details produit
        Route::livewire("/products/{product}/show", "pages::products.show")
            ->middleware("permission:" . Permissions::view(Product::class))
            ->name("products.show");

        Route::livewire("/products/{product}/edit", "pages::products.edit")
            ->middleware("permission:" . Permissions::view(Product::class))
            ->name("products.edit");
    });

    // Commandes
    Route::prefix("orders")->name("orders.")->group(function () {
        Route::livewire("/list", "pages::orders.page")
            ->middleware("permission:" . Permissions::viewAny(Commande::class))
            ->name("list");

        Route::livewire("/create", "pages::orders.create")
            ->middleware("permission:" . Permissions::create(Commande::class))
            ->name("create");

        Route::livewire("/edit/{commande_id}", "pages::orders.edit")
            ->middleware("permission:" . Permissions::view(Commande::class))
            ->name("edit");

        Route::livewire("/view/{commande_id}", "pages::orders.view")
            ->middleware("permission:" . Permissions::view(Commande::class))
            ->name("view");

        Route::livewire("/facture/{commande_id}", "pages::orders.facture.page")
            ->middleware("permission:" . Permissions::view(Commande::class))
            ->name("facture");
    });

    // Réception / Approvisionnement
    Route::prefix("reception")->name("reception_commande.")->group(function () {
        Route::livewire("/list", "pages::aprovisionement.page")
            ->middleware("permission:" . Permissions::viewAny(ReceptionCommande::class))
            ->name("list");

        Route::livewire("/create", "pages::aprovisionement.reception.create")
            ->middleware("permission:" . Permissions::create(ReceptionCommande::class))
            ->name("create");

        Route::livewire("/edit/{bon}", "pages::aprovisionement.reception.edit")
            ->middleware("permission:" . Permissions::view(ReceptionCommande::class))
            ->name("edit");

        Route::livewire("/view/{bon}", "pages::aprovisionement.reception.view")
            ->middleware("permission:" . Permissions::view(ReceptionCommande::class))
            ->name("view");
    });

    // Facturation
    Route::prefix("facturation")->name("facturation.")->group(function () {
        Route::livewire("/list", "pages::facturation.page")
            ->middleware("permission:" . Permissions::viewAny(Facture::class))
            ->name("list");

        Route::livewire("/create", "pages::facturation.create")
            ->middleware("permission:" . Permissions::create(Facture::class))
            ->name("create");

        Route::livewire("/edit/{facture}", "pages::facturation.edit")
            ->middleware("permission:" . Permissions::view(Facture::class))
            ->name("edit");
    });

    // PDF Bon de commande
    Route::get("/commandes/{id}/bon-commande/pdf", [BonCommandePdfController::class, "download"])
        ->middleware("permission:" . Permissions::view(Commande::class))
        ->name("bon-commande.pdf");

    Route::get('/reception/pdf/{bon}', \App\Http\Controllers\ReceptionController::class)
        ->middleware("permission:" . Permissions::view(Reception::class))
        ->name('reception_commande.pdf');

    Route::get('/facture/pdf/{facture}', \App\Http\Controllers\FactureController::class)
        ->middleware("permission:" . Permissions::view(Facture::class))
        ->name('facture.pdf');

    // POS System
    // Pas de modèle Eloquent dédié : on garde la même convention de slug
    // ("view_any_pos") pour rester cohérent avec le reste du système.
    Route::livewire("/pos", "pages::pos.page")
        ->middleware("permission:" . Permissions::slug(Permissions::VIEW_ANY, 'Pos'))
        ->name("pos");
});

require __DIR__ . "/settings.php";
