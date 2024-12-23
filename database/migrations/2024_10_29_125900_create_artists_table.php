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
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->primary()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nationality')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('is_active')->default(true); 
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
