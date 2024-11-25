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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('episode_number');
            $table->integer('season_number')->default(1);
            $table->text('description')->nullable();
            $table->string('video_path');  
            $table->string('thumbnail_path')->nullable(); 
            $table->integer('file_size')->nullable()->comment('Size in bytes'); 
            $table->integer('duration')->comment('Duration in seconds');
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        
            
            $table->index(['season_id', 'episode_number']);
            $table->index('views_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
