<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed zones, states, LGAs, and PHCs
        $this->call(NigeriaStatesSeeder::class);
        
        // Seed beneficiaries
        $this->call(BeneficiariesSeeder::class);

        // Create an admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@surep.gov.ng',
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
