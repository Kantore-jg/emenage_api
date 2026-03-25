<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('lieu')->nullable();
            $table->enum('type', ['reunion', 'vaccination', 'marche', 'ceremonie', 'sport', 'formation', 'autre'])->default('autre');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('geographic_area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('announcement_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('date_debut');
            $table->index('type');
            $table->index('geographic_area_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
