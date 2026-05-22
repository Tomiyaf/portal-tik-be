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
        Schema::create('intercom_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_id')->constrained('gates');
            $table->enum('call_status', ['incoming', 'accepted', 'rejected', 'ended', 'missed']);
            $table->foreignId('answered_by')->nullable()->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intercom_calls');
    }
};
