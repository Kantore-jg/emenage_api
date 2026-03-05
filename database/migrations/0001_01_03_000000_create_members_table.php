<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->string('nom');
            $table->enum('type', ['permanent', 'invite']);
            $table->enum('statut', ['present', 'parti'])->default('present');
            $table->enum('statut_validation', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            $table->string('photo_cni')->nullable();
            $table->integer('age');
            $table->string('telephone', 20)->nullable();
            $table->timestamps();

            $table->index('household_id');
            $table->index('type');
            $table->index('statut');
            $table->index('statut_validation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
