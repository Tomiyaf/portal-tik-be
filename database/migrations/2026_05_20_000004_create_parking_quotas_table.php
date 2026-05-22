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
        Schema::create('parking_quotas', function (Blueprint $table) {
            $table->id();
            $table->integer('total_slots');
            $table->integer('used_slots');
            $table->enum('status', ['available', 'nearly_full', 'full']);
            $table->boolean('auto_restrict_student');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_quotas');
    }
};
