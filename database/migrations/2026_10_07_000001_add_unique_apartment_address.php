<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->unique(
                ['geographic_area_id', 'avenue', 'numero'],
                'apartments_area_avenue_numero_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropUnique('apartments_area_avenue_numero_unique');
        });
    }
};
