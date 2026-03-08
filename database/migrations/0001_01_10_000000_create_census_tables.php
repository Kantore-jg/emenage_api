<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter le rôle agent_recensement
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('citoyen','collinaire','zonal','communal','provincial','ministere','admin','police','agent_recensement') DEFAULT 'citoyen'");

        // Campagnes de recensement
        Schema::create('censuses', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('statut', ['brouillon', 'actif', 'termine', 'archive'])->default('brouillon');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('geographic_area_id')->nullable()->constrained('geographic_areas')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('statut');
        });

        // Champs du formulaire (type Google Forms)
        Schema::create('census_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('census_id')->constrained('censuses')->cascadeOnDelete();
            $table->string('label');
            $table->enum('type', ['text', 'number', 'date', 'select', 'multi_select', 'boolean', 'textarea']);
            $table->json('options')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('field_order')->default(0);
            $table->timestamps();

            $table->index('census_id');
        });

        // Agents assignés à une campagne avec zone
        Schema::create('census_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('census_id')->constrained('censuses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('geographic_area_id')->nullable()->constrained('geographic_areas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['census_id', 'user_id']);
        });

        // Réponses collectées (une par citoyen/ménage interrogé)
        Schema::create('census_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('census_id')->constrained('censuses')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('geographic_area_id')->nullable()->constrained('geographic_areas')->nullOnDelete();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_phone')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index('census_id');
            $table->index('agent_id');
        });

        // Valeurs individuelles (EAV : chaque champ = une ligne)
        Schema::create('census_response_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('census_responses')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('census_fields')->cascadeOnDelete();
            $table->text('value')->nullable();

            $table->index('response_id');
            $table->index('field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('census_response_values');
        Schema::dropIfExists('census_responses');
        Schema::dropIfExists('census_agents');
        Schema::dropIfExists('census_fields');
        Schema::dropIfExists('censuses');

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('citoyen','collinaire','zonal','communal','provincial','ministere','admin','police') DEFAULT 'citoyen'");
    }
};
