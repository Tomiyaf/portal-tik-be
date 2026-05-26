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
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('device_uid')->unique();
            $table->foreignId('gate_id')->constrained('gates');
            // $table->string('ip_address');
            $table->string('firmware_version');
            $table->enum('status', ['online', 'offline']);
            $table->timestamp('last_online_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};
