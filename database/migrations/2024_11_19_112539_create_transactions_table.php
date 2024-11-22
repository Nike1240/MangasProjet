<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
    
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // ID de transaction FedaPay
            $table->string('phone'); // Numéro de téléphone
            $table->decimal('amount', 10, 2); // Montant
            $table->string('currency')->default('XOF'); // Devise
            $table->string('status'); // Statut de la transaction
            $table->string('payment_method')->nullable(); // Méthode de paiement
            $table->text('description')->nullable(); // Description optionnelle
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->timestamps(); // created_at et updated_at
        });
            
    }

   
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
    
};
