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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained();
            $table->string('name');
            $table->enum('type', ['percentage', 'fixed_amount']);
            $table->decimal('value', 10, 2);
            $table->integer('min_quantity')->nullable(); // Réduction applicable à partir de X unités
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
