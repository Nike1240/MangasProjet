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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('video_url');
            $table->integer('duration')->comment('Duration in seconds');
            $table->integer('reward_amount')->comment('D-Keys awarded for full view');
            $table->enum('status', ['active', 'inactive', 'scheduled']);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('advertiser_name');
            $table->decimal('daily_budget', 10, 2);
            $table->integer('total_views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
