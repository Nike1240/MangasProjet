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
        Schema::create('d_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('package_id')->nullable()->constrained();
            $table->foreignId('transaction_id')->nullable()->constrained('d_key_transactions');
            $table->integer('key_remaining');
            $table->enum('source_type', ['purchase', 'subscription', 'ad_reward']);
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'consumed', 'paused'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('d_keys');
    }
};
