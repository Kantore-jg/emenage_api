<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('geographic_area_id')->constrained('geographic_areas')->cascadeOnDelete();
            $table->string('avenue');
            $table->string('numero', 50);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('geographic_area_id');
            $table->index('avenue');
            $table->index('owner_id');
        });

        Schema::table('households', function (Blueprint $table) {
            $table->foreignId('apartment_id')->nullable()->after('geographic_area_id')
                ->constrained('apartments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropConstrainedForeignId('apartment_id');
        });

        Schema::dropIfExists('apartments');
    }
};
