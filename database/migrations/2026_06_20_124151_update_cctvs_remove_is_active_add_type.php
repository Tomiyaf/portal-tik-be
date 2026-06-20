<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cctvs', function (Blueprint $table) {

            $table->enum('type', [
                'monitor',
                'intercom',
            ])->default('monitor')
              ->after('stream_url');

            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('cctvs', function (Blueprint $table) {

            $table->boolean('is_active')
                ->default(true);

            $table->dropColumn('type');
        });
    }
};