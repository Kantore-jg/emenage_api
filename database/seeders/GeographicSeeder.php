<?php

namespace Database\Seeders;

use App\Models\GeographicArea;
use App\Models\GeographicLevel;
use Illuminate\Database\Seeder;

class GeographicSeeder extends Seeder
{
    public function run(): void
    {
        $province = GeographicLevel::create(['name' => 'Province', 'slug' => 'province', 'level_order' => 1]);
        $commune  = GeographicLevel::create(['name' => 'Commune',  'slug' => 'commune',  'level_order' => 2]);
        $zone     = GeographicLevel::create(['name' => 'Zone',     'slug' => 'zone',     'level_order' => 3]);
        $colline  = GeographicLevel::create(['name' => 'Colline',  'slug' => 'colline',  'level_order' => 4]);

        $dataPath = base_path('data.json');
        if (!file_exists($dataPath)) {
            $dataPath = base_path('donnees/data.json');
        }

        $data = json_decode(file_get_contents($dataPath), true);

        if (!$data) {
            $this->command->error('Impossible de lire data.json');
            return;
        }

        $totalProvinces = 0;
        $totalCommunes = 0;
        $totalZones = 0;
        $totalCollines = 0;

        foreach ($data as $provinceName => $communes) {
            $provinceArea = GeographicArea::create([
                'name' => $provinceName,
                'level_id' => $province->id,
                'parent_id' => null,
            ]);
            $totalProvinces++;

            foreach ($communes as $communeName => $zones) {
                $communeArea = GeographicArea::create([
                    'name' => $communeName,
                    'level_id' => $commune->id,
                    'parent_id' => $provinceArea->id,
                ]);
                $totalCommunes++;

                foreach ($zones as $zoneName => $collines) {
                    $zoneArea = GeographicArea::create([
                        'name' => $zoneName,
                        'level_id' => $zone->id,
                        'parent_id' => $communeArea->id,
                    ]);
                    $totalZones++;

                    foreach ($collines as $collineName) {
                        GeographicArea::create([
                            'name' => $collineName,
                            'level_id' => $colline->id,
                            'parent_id' => $zoneArea->id,
                        ]);
                        $totalCollines++;
                    }
                }
            }
        }

        $this->command->info("Données géographiques importées:");
        $this->command->info("  {$totalProvinces} provinces");
        $this->command->info("  {$totalCommunes} communes");
        $this->command->info("  {$totalZones} zones");
        $this->command->info("  {$totalCollines} collines");
    }
}
