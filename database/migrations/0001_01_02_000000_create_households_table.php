<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chef_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('quartier');
            $table->text('adresse');
            $table->timestamps();

            $table->index('quartier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
