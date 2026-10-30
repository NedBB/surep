<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\Phc;
use App\Models\Beneficiary;
use Carbon\Carbon;

class BeneficiariesSeeder extends Seeder
{
    public function run(): void
    {
        $states = State::with('phcs')->get();

        foreach ($states as $state) {
            if ($state->phcs->isEmpty()) {
                continue;
            }

            // Create between 5-50 beneficiaries per state
            $count = rand(5, 50);

            for ($i = 0; $i < $count; $i++) {
                $phc = $state->phcs->random();
                $registrationDate = Carbon::now()->subMonths(rand(1, 8));
                
                $statuses = ['active', 'active', 'active', 'completed', 'dropped_out'];
                $status = $statuses[array_rand($statuses)];

                $beneficiary = Beneficiary::create([
                    'phc_id' => $phc->id,
                    'state_id' => $state->id,
                    'lga_id' => $phc->lga_id,
                    'registration_number' => $state->registration_prefix . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                    'date_registered' => $registrationDate,
                    'enrollment_date' => $registrationDate,
                    'surname' => $this->generateRandomName(),
                    'first_name' => $this->generateRandomFirstName(),
                    'age' => rand(18, 40),
                    'address' => 'Sample Address ' . ($i + 1),
                    'village_ward' => 'Ward ' . rand(1, 10),
                    'phone_number' => '080' . rand(10000000, 99999999),
                    'phone_owned_by' => rand(0, 1) ? 'Beneficiary' : 'Relative',
                    
                    // ANC Services
                    'anc1_attended' => true,
                    'anc1_date' => $registrationDate->copy()->addDays(rand(1, 7)),
                    
                    'anc2_attended' => rand(0, 10) > 2,
                    'anc2_date' => rand(0, 10) > 2 ? $registrationDate->copy()->addWeeks(rand(4, 6)) : null,
                    
                    'anc3_attended' => rand(0, 10) > 3,
                    'anc3_date' => rand(0, 10) > 3 ? $registrationDate->copy()->addWeeks(rand(8, 12)) : null,
                    
                    'anc4_attended' => rand(0, 10) > 4,
                    'anc4_date' => rand(0, 10) > 4 ? $registrationDate->copy()->addWeeks(rand(14, 20)) : null,
                    
                    // Delivery
                    'skilled_delivery_attended' => $status === 'completed' || rand(0, 10) > 5,
                    'delivery_date' => ($status === 'completed' || rand(0, 10) > 5) ? $registrationDate->copy()->addMonths(rand(6, 9)) : null,
                    
                    // Post-natal
                    'post_natal_attended' => $status === 'completed' || rand(0, 10) > 6,
                    'post_natal_date' => ($status === 'completed' || rand(0, 10) > 6) ? $registrationDate->copy()->addMonths(rand(6, 9))->addWeeks(2) : null,
                    
                    'immunization_attended' => $status === 'completed' || rand(0, 10) > 6,
                    'immunization_date' => ($status === 'completed' || rand(0, 10) > 6) ? $registrationDate->copy()->addMonths(rand(6, 9))->addWeeks(3) : null,
                    
                    'status' => $status,
                    'last_attendance_date' => Carbon::now()->subDays(rand(1, 30)),
                    'expiry_date' => $registrationDate->copy()->addMonths(9),
                ]);

                // Calculate entitlement
                $entitlement = $beneficiary->calculateEntitlement();
                $disbursed = rand(0, 10) > 3 ? $entitlement * (rand(50, 100) / 100) : 0;

                $beneficiary->update([
                    'total_entitled_amount' => $entitlement,
                    'total_disbursed_amount' => $disbursed,
                    'pending_amount' => $entitlement - $disbursed,
                ]);

                // Update PHC statistics
                $phc->increment('total_beneficiaries');
                if ($status === 'active') {
                    $phc->increment('active_beneficiaries');
                } else {
                    $phc->increment('exited_beneficiaries');
                }
                $phc->increment('total_cash_disbursed', $disbursed);
            }

            // Update state statistics
            $state->update([
                'total_beneficiaries' => $state->phcs->sum('total_beneficiaries'),
                'active_beneficiaries' => $state->phcs->sum('active_beneficiaries'),
                'exited_beneficiaries' => $state->phcs->sum('exited_beneficiaries'),
                'total_cash_disbursed' => $state->phcs->sum('total_cash_disbursed'),
                'phc_count' => $state->phcs->count(),
            ]);
        }

        $this->command->info('Beneficiaries seeded successfully!');
    }

    private function generateRandomName(): string
    {
        $names = ['Adebayo', 'Okafor', 'Ibrahim', 'Mohammed', 'Chukwu', 'Okonkwo', 'Bello', 'Eze', 'Abubakar', 'Nwosu', 'Ogunyemi', 'Yusuf', 'Musa', 'Okeke', 'Hassan'];
        return $names[array_rand($names)];
    }

    private function generateRandomFirstName(): string
    {
        $names = ['Amina', 'Blessing', 'Chioma', 'Fatima', 'Grace', 'Hadiza', 'Ifunanya', 'Joy', 'Kehinde', 'Ladi', 'Mary', 'Ngozi', 'Oluchi', 'Peace', 'Queen', 'Rose', 'Sarah', 'Taiwo', 'Uche', 'Victoria'];
        return $names[array_rand($names)];
    }
}
