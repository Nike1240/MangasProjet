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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); // Ajouté
            $table->string('slug')->unique(); // Ajouté
            $table->integer('page_number');
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('file_size')->nullable()->comment('Size in bytes');
            $table->enum('status', ['draft', 'published', 'archived'])->default('published'); // Ajouté
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->timestamps();
        
            $table->index(['chapter_id', 'page_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
