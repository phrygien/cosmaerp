<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Cosma Parfumeries');
            $table->string('app_name');
            $table->string('app_version')->default('1.0.0');
            $table->string('app_description')->nullable();
            $table->string('app_logo')->nullable();
            $table->string('currency')->default('EUR');
            $table->string('currency_symbol')->default('EUR');
            $table->string('currency_code')->default('EUR');
            $table->string('timezone')->default('Paris/France');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
