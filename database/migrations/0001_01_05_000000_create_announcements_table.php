<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre');
            $table->text('contenu');
            $table->string('autorite');
            $table->date('date');
            $table->timestamps();

            $table->index('author_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
