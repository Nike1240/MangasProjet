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
        Schema::create('subscriptions_artist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('plan_type', ['free', 'pro']);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->boolean('auto_renewal')->default(false);
            $table->integer('price_paid')->nullable();
            $table->timestamps();

              // Index pour optimiser les recherches
          $table->index(['user_id', 'status']);
          $table->index('end_date');
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions_artist');
    }
};
