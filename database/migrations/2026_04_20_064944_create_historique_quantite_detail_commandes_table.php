<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_quantite_detail_commande', function (Blueprint $table) {
            $table->id(); // This creates an unsignedBigInteger

            // Use unsignedBigInteger to match $table->id() in referenced tables
            $table->unsignedBigInteger('detail_commande_id');
            $table->unsignedBigInteger('commande_id');
            $table->unsignedBigInteger('product_id');

            // Add foreign key constraints separately
            $table->foreign('detail_commande_id')
                ->references('id')
                ->on('detail_commande')
                ->cascadeOnDelete();

            $table->foreign('commande_id')
                ->references('id')
                ->on('commande')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('product');

            $table->decimal('ancienne_quantite', 10, 2);
            $table->decimal('nouvelle_quantite', 10, 2);
            $table->string('motif')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_quantite_detail_commande');
    }
};
