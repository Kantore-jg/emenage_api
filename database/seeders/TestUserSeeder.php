<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\GeographicArea;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Chaîne géographique: BUJUMBURA → MUKAZA → Nyakabiga → Quartier Nyakabiga
        $province  = GeographicArea::where('name', 'BUJUMBURA')->first();
        $commune   = GeographicArea::where('name', 'MUKAZA')->where('parent_id', $province?->id)->first();
        $zone      = GeographicArea::where('name', 'Nyakabiga')->where('parent_id', $commune?->id)->first();
        $colline   = GeographicArea::where('name', 'Quartier Nyakabiga')->where('parent_id', $zone?->id)->first();
        $colline2  = GeographicArea::where('name', 'Quartier Mugoboka')->where('parent_id', $zone?->id)->first();

        // 1. Admin — voit tout le pays
        $admin = User::create([
            'nom' => 'Admin Système',
            'role' => 'admin',
            'telephone' => '79000001',
            'email' => 'admin@emenage.bi',
            'password' => 'admin123',
            'geographic_area_id' => null,
        ]);

        // 2. Ministère — inscrit par l'admin, voit tout le pays
        $ministere = User::create([
            'nom' => 'Jean Ministre',
            'role' => 'ministere',
            'telephone' => '79000002',
            'email' => 'ministere@emenage.bi',
            'password' => 'test123',
            'created_by' => $admin->id,
            'geographic_area_id' => null,
        ]);

        // 3. Provincial — inscrit par le ministère, assigné à BUJUMBURA
        $provincial = User::create([
            'nom' => 'Pierre Provincial',
            'role' => 'provincial',
            'telephone' => '79000003',
            'email' => 'provincial@emenage.bi',
            'password' => 'test123',
            'created_by' => $ministere->id,
            'geographic_area_id' => $province?->id,
        ]);

        // 4. Communal — inscrit par le provincial, assigné à MUKAZA
        $communal = User::create([
            'nom' => 'Marie Communal',
            'role' => 'communal',
            'telephone' => '79000004',
            'email' => 'communal@emenage.bi',
            'password' => 'test123',
            'created_by' => $provincial->id,
            'geographic_area_id' => $commune?->id,
        ]);

        // 5. Zonal — inscrit par le communal, assigné à Nyakabiga
        $zonal = User::create([
            'nom' => 'Paul Zonal',
            'role' => 'zonal',
            'telephone' => '79000005',
            'email' => 'zonal@emenage.bi',
            'password' => 'test123',
            'created_by' => $communal->id,
            'geographic_area_id' => $zone?->id,
        ]);

        // 6. Collinaire — inscrit par le zonal, assigné à Quartier Nyakabiga
        $collinaire = User::create([
            'nom' => 'Claude Collinaire',
            'role' => 'collinaire',
            'telephone' => '79000006',
            'email' => 'collinaire@emenage.bi',
            'password' => 'test123',
            'created_by' => $zonal->id,
            'geographic_area_id' => $colline?->id,
        ]);

        // 7. Citoyen — inscrit par le collinaire
        $citoyen1 = User::create([
            'nom' => 'Alain Citoyen',
            'role' => 'citoyen',
            'telephone' => '79000007',
            'email' => 'alain@example.bi',
            'password' => 'test123',
            'created_by' => $collinaire->id,
            'geographic_area_id' => $colline?->id,
        ]);

        $apt1 = Apartment::create([
            'owner_id' => $citoyen1->id,
            'geographic_area_id' => $colline?->id,
            'avenue' => 'Avenue de la Liberté',
            'numero' => '15',
            'description' => 'Maison avec 4 chambres, cour intérieure',
        ]);

        Household::create([
            'chef_id' => $citoyen1->id,
            'quartier' => $colline?->name ?? 'Quartier Nyakabiga',
            'adresse' => 'Avenue de la Liberté, N°15',
            'geographic_area_id' => $colline?->id,
            'apartment_id' => $apt1->id,
        ]);

        $citoyen2 = User::create([
            'nom' => 'Diane Citoyenne',
            'role' => 'citoyen',
            'telephone' => '79000008',
            'email' => 'diane@example.bi',
            'password' => 'test123',
            'created_by' => $collinaire->id,
            'geographic_area_id' => $colline2?->id ?? $colline?->id,
        ]);

        $apt2 = Apartment::create([
            'owner_id' => $citoyen2->id,
            'geographic_area_id' => $colline2?->id ?? $colline?->id,
            'avenue' => 'Rue du Commerce',
            'numero' => '7',
            'description' => 'Maison à étage, 3 chambres',
        ]);

        Household::create([
            'chef_id' => $citoyen2->id,
            'quartier' => $colline2?->name ?? $colline?->name ?? 'Quartier Mugoboka',
            'adresse' => 'Rue du Commerce, N°7',
            'geographic_area_id' => $colline2?->id ?? $colline?->id,
            'apartment_id' => $apt2->id,
        ]);

        // 8. Police — inscrit par l'admin
        User::create([
            'nom' => 'Eric Police',
            'role' => 'police',
            'telephone' => '79000009',
            'email' => 'police@emenage.bi',
            'password' => 'test123',
            'created_by' => $admin->id,
            'geographic_area_id' => $commune?->id,
        ]);

        $this->command->info('');
        $this->command->info('Comptes créés (hiérarchie):');
        $this->command->info('  Admin:       admin@emenage.bi / admin123       → tout le pays');
        $this->command->info('  Ministère:   ministere@emenage.bi / test123    → tout le pays');
        $this->command->info('  Provincial:  provincial@emenage.bi / test123   → BUJUMBURA');
        $this->command->info('  Communal:    communal@emenage.bi / test123     → MUKAZA');
        $this->command->info('  Zonal:       zonal@emenage.bi / test123        → Nyakabiga');
        $this->command->info('  Collinaire:  collinaire@emenage.bi / test123   → Quartier Nyakabiga');
        $this->command->info('  Citoyens:    alain@example.bi / test123');
        $this->command->info('  Police:      police@emenage.bi / test123       → MUKAZA');
    }
}
