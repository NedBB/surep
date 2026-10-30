<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Zone;
use App\Models\State;
use App\Models\Lga;
use App\Models\GeneralHospital;
use App\Models\Phc;

class NigeriaStatesSeeder extends Seeder
{
    public function run(): void
    {
        // Create Zones
        $zones = [
            ['name' => 'North Central', 'code' => 'NC'],
            ['name' => 'North East', 'code' => 'NE'],
            ['name' => 'North West', 'code' => 'NW'],
            ['name' => 'South East', 'code' => 'SE'],
            ['name' => 'South South', 'code' => 'SS'],
            ['name' => 'South West', 'code' => 'SW'],
        ];

        foreach ($zones as $zoneData) {
            Zone::firstOrCreate(['code' => $zoneData['code']], $zoneData);
        }

        // Create States
        $states = [
            // South West
            ['name' => 'Lagos', 'code' => 'LA', 'zone_code' => 'SW', 'prefix' => 'LA/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Ogun', 'code' => 'OG', 'zone_code' => 'SW', 'prefix' => 'OG/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Oyo', 'code' => 'OY', 'zone_code' => 'SW', 'prefix' => 'OY/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Osun', 'code' => 'OS', 'zone_code' => 'SW', 'prefix' => 'OS/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Ondo', 'code' => 'ON', 'zone_code' => 'SW', 'prefix' => 'ON/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Ekiti', 'code' => 'EK', 'zone_code' => 'SW', 'prefix' => 'EK/CCT/', 'bank' => 'Stanbic'],
            
            // South South
            ['name' => 'Rivers', 'code' => 'RI', 'zone_code' => 'SS', 'prefix' => 'RI/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Bayelsa', 'code' => 'BY', 'zone_code' => 'SS', 'prefix' => 'BY/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Delta', 'code' => 'DE', 'zone_code' => 'SS', 'prefix' => 'DE/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Edo', 'code' => 'ED', 'zone_code' => 'SS', 'prefix' => 'ED/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Cross River', 'code' => 'CR', 'zone_code' => 'SS', 'prefix' => 'CR/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Akwa Ibom', 'code' => 'AK', 'zone_code' => 'SS', 'prefix' => 'AK/CCT/', 'bank' => 'Stanbic'],
            
            // South East
            ['name' => 'Anambra', 'code' => 'AN', 'zone_code' => 'SE', 'prefix' => 'AN/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Enugu', 'code' => 'EN', 'zone_code' => 'SE', 'prefix' => 'EN/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Ebonyi', 'code' => 'EB', 'zone_code' => 'SE', 'prefix' => 'EB/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Abia', 'code' => 'AB', 'zone_code' => 'SE', 'prefix' => 'AB/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Imo', 'code' => 'IM', 'zone_code' => 'SE', 'prefix' => 'IM/CCT/', 'bank' => 'Stanbic'],
            
            // North Central
            ['name' => 'FCT', 'code' => 'FCT', 'zone_code' => 'NC', 'prefix' => 'FCT/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Niger', 'code' => 'NG', 'zone_code' => 'NC', 'prefix' => 'NG/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Kwara', 'code' => 'KW', 'zone_code' => 'NC', 'prefix' => 'KW/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Kogi', 'code' => 'KG', 'zone_code' => 'NC', 'prefix' => 'KG/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Benue', 'code' => 'BE', 'zone_code' => 'NC', 'prefix' => 'BE/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Plateau', 'code' => 'PL', 'zone_code' => 'NC', 'prefix' => 'PL/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Nasarawa', 'code' => 'NS', 'zone_code' => 'NC', 'prefix' => 'NS/CCT/', 'bank' => 'Stanbic'],
            
            // North West
            ['name' => 'Kaduna', 'code' => 'KD', 'zone_code' => 'NW', 'prefix' => 'KD/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Kano', 'code' => 'KN', 'zone_code' => 'NW', 'prefix' => 'KN/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Katsina', 'code' => 'KT', 'zone_code' => 'NW', 'prefix' => 'KT/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Zamfara', 'code' => 'ZM', 'zone_code' => 'NW', 'prefix' => 'ZM/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Sokoto', 'code' => 'SO', 'zone_code' => 'NW', 'prefix' => 'SO/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Kebbi', 'code' => 'KE', 'zone_code' => 'NW', 'prefix' => 'KE/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Jigawa', 'code' => 'JG', 'zone_code' => 'NW', 'prefix' => 'JG/CCT/', 'bank' => 'Zenith'],
            
            // North East
            ['name' => 'Bauchi', 'code' => 'BA', 'zone_code' => 'NE', 'prefix' => 'BA/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Gombe', 'code' => 'GO', 'zone_code' => 'NE', 'prefix' => 'GO/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Borno', 'code' => 'BO', 'zone_code' => 'NE', 'prefix' => 'BO/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Yobe', 'code' => 'YO', 'zone_code' => 'NE', 'prefix' => 'YO/CCT/', 'bank' => 'Stanbic'],
            ['name' => 'Adamawa', 'code' => 'AD', 'zone_code' => 'NE', 'prefix' => 'AD/CCT/', 'bank' => 'Zenith'],
            ['name' => 'Taraba', 'code' => 'TR', 'zone_code' => 'NE', 'prefix' => 'TR/CCT/', 'bank' => 'Stanbic'],
        ];

        foreach ($states as $stateData) {
            $zone = Zone::where('code', $stateData['zone_code'])->first();
            if ($zone) {
                State::firstOrCreate(
                    ['code' => $stateData['code']],
                    [
                        'name' => $stateData['name'],
                        'zone_id' => $zone->id,
                        'registration_prefix' => $stateData['prefix'],
                        'partner_bank' => $stateData['bank'],
                    ]
                );
            }
        }

        // Create sample LGAs and PHCs for FCT
        $fct = State::where('code', 'FCT')->first();
        if ($fct) {
            $lgas = [
                ['name' => 'Bwari', 'code' => 'BWR'],
                ['name' => 'Kuje', 'code' => 'KJE'],
                ['name' => 'AMAC', 'code' => 'AMC'],
            ];

            foreach ($lgas as $lgaData) {
                $lga = Lga::firstOrCreate(
                    ['state_id' => $fct->id, 'name' => $lgaData['name']],
                    ['code' => $lgaData['code']]
                );

                // Create PHCs
                $phcs = [
                    ['name' => 'Dutse Alhaji PHC', 'address' => 'Dutse, Bwari'],
                    ['name' => 'Kuje PHC', 'address' => 'Kuje Town'],
                    ['name' => 'Kubwa General Hospital', 'address' => 'Kubwa, Bwari'],
                ];

                foreach ($phcs as $phcData) {
                    Phc::firstOrCreate(
                        ['state_id' => $fct->id, 'lga_id' => $lga->id, 'name' => $phcData['name']],
                        [
                            'address' => $phcData['address'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('Nigeria states, LGAs, and PHCs seeded successfully!');
    }
}
