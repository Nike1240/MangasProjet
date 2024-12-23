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
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); 
            $table->string('slug')->unique();
            $table->integer('number');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->integer('episodes_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['content_id', 'number']);
            $table->index('views_count');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
