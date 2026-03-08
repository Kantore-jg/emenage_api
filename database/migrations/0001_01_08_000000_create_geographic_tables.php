<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geographic_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('level_order');
            $table->timestamps();

            $table->index('level_order');
        });

        Schema::create('geographic_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('level_id')->constrained('geographic_levels')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('geographic_areas')->cascadeOnDelete();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('level_id');
            $table->index('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('geographic_area_id')
                ->nullable()
                ->after('role')
                ->constrained('geographic_areas')
                ->nullOnDelete();
        });

        Schema::table('households', function (Blueprint $table) {
            $table->foreignId('geographic_area_id')
                ->nullable()
                ->after('quartier')
                ->constrained('geographic_areas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropConstrainedForeignId('geographic_area_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('geographic_area_id');
        });

        Schema::dropIfExists('geographic_areas');
        Schema::dropIfExists('geographic_levels');
    }
};
