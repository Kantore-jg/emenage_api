<?php

namespace Database\Seeders;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Compte admin principal
        $admin = User::create([
            'nom' => 'Admin Système',
            'role' => 'admin',
            'telephone' => '79000001',
            'email' => 'admin@ubuzimahub.bi',
            'password' => 'admin123',
        ]);

        // Créés par l'admin
        $chef = User::create([
            'nom' => 'Pierre Chef',
            'role' => 'chef_quartier',
            'telephone' => '79000002',
            'email' => 'chef@ubuzimahub.bi',
            'password' => 'test123',
            'created_by' => $admin->id,
        ]);

        User::create([
            'nom' => 'Paul Ministre',
            'role' => 'ministere',
            'telephone' => '79000003',
            'email' => 'ministere@ubuzimahub.bi',
            'password' => 'test123',
            'created_by' => $admin->id,
        ]);

        User::create([
            'nom' => 'Jacques Police',
            'role' => 'police',
            'telephone' => '79000004',
            'email' => 'police@ubuzimahub.bi',
            'password' => 'test123',
            'created_by' => $admin->id,
        ]);

        // Citoyens créés par le chef de quartier
        $citoyen1 = User::create([
            'nom' => 'Jean Citoyen',
            'role' => 'citoyen',
            'telephone' => '79000005',
            'email' => 'jean@example.bi',
            'password' => 'test123',
            'created_by' => $chef->id,
        ]);

        Household::create([
            'chef_id' => $citoyen1->id,
            'quartier' => 'Buyenzi',
            'adresse' => 'Avenue de la Liberté, N°15',
        ]);

        $citoyen2 = User::create([
            'nom' => 'Marie Citoyenne',
            'role' => 'citoyen',
            'telephone' => '79000006',
            'email' => 'marie@example.bi',
            'password' => 'test123',
            'created_by' => $chef->id,
        ]);

        Household::create([
            'chef_id' => $citoyen2->id,
            'quartier' => 'Bwiza',
            'adresse' => 'Rue du Commerce, N°7',
        ]);

        $this->command->info('Comptes créés:');
        $this->command->info('  Admin:  admin@ubuzimahub.bi / admin123');
        $this->command->info('  Chef:   chef@ubuzimahub.bi / test123');
        $this->command->info('  Autres: test123');
    }
}
