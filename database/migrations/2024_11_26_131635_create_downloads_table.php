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
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Remplacement des colonnes spécifiques par des colonnes polymorphiques
            $table->unsignedBigInteger('downloadable_id');
            $table->string('downloadable_type');
            
            $table->timestamp('downloaded_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('is_offline')->default(false);
            $table->timestamps();

            // Index pour la recherche polymorphique
            $table->index(['downloadable_id', 'downloadable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
