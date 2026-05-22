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
        Schema::create('visitor_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intercom_call_id')->constrained('intercom_calls');
            $table->text('image_url');
            $table->timestamp('captured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_snapshots');
    }
};
