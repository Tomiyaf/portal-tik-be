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
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('gate_id')->constrained('gates');
            $table->enum('access_status', ['success', 'pending', 'failed']);
            $table->enum('access_method', ['mobile', 'web', 'desktop']);
            // $table->string('triggered_by');
            $table->enum('action', ['open', 'close', 'entry', 'exit']);
            $table->text('notes')->nullable();
            $table->timestamp('updated_at');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
