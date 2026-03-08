<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('citoyen','collinaire','zonal','communal','provincial','ministere','admin','police') DEFAULT 'citoyen'");

        DB::table('users')->where('role', 'chef_quartier')->update(['role' => 'collinaire']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'collinaire')->update(['role' => 'chef_quartier']);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('citoyen','chef_quartier','ministere','admin','police') DEFAULT 'citoyen'");
    }
};
