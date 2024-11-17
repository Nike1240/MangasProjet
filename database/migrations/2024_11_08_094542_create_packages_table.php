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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_type_id')->constrained();
            $table->string('name');
            $table->boolean('is_pack')->default(false); // Indique si c'est un pack avec prix fixe
            $table->decimal('unit_price', 10, 2)->nullable(); // Prix unitaire pour achat standard
            $table->decimal('pack_price', 10, 2)->nullable(); // Prix fixe pour un pack
            $table->integer('pack_quantity')->nullable(); // Nombre de DKeys dans le pack
            $table->string('duration')->nullable(); // Pour les abonnements
            $table->integer('pages_per_dkey');
            $table->integer('episodes_per_dkey');
            $table->integer('min_quantity')->default(1); // Quantité minimum d'achat
            $table->integer('max_quantity')->nullable(); // Quantité maximum d'achat (null = illimité)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
