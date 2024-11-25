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
        Schema::create('d_key_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('package_id')->constrained('packages');
            $table->foreignId('discount_id')->nullable()->constrained();
            $table->integer('quantity'); // Quantité achetée
            $table->decimal('unit_price', 10, 2)->nullable(); // Prix unitaire au moment de l'achat
            $table->decimal('pack_price', 10, 2)->nullable(); // Prix du pack si applicable
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2); // Montant final total
            $table->string('payment_method');
            $table->enum('status', ['pending', 'completed', 'failed']);
            $table->string('transaction_reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('d_key_transactions');
    }
};
