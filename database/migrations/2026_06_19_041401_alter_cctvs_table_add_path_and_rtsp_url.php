<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cctvs', function (Blueprint $table) {
            $table->string('path')
                ->unique()
                ->after('camera_name');
        });
    }

    public function down(): void
    {
        Schema::table('cctvs', function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }
};