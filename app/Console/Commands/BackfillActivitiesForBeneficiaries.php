<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\Activity;
use Illuminate\Console\Command;

class BackfillActivitiesForBeneficiaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:backfill 
                            {--phc_id= : Filter by PHC ID}
                            {--state_id= : Filter by State ID}
                            {--lga_id= : Filter by LGA ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create activity records for beneficiaries that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phcId = $this->option('phc_id');
        $stateId = $this->option('state_id');
        $lgaId = $this->option('lga_id');
        
        $query = Beneficiary::query();
        
        if ($phcId) {
            $query->where('phc_id', $phcId);
        }
        if ($stateId) {
            $query->where('state_id', $stateId);
        }
        if ($lgaId) {
            $query->where('lga_id', $lgaId);
        }
        
        // Get all beneficiaries and check which ones don't have activities
        $allBeneficiaries = $query->get();
        $existingActivityIds = Activity::pluck('beneficiary_id')->toArray();
        $beneficiariesWithoutActivities = $allBeneficiaries->filter(function($ben) use ($existingActivityIds) {
            return !in_array($ben->id, $existingActivityIds);
        });
        
        $total = $beneficiariesWithoutActivities->count();
        
        if ($total === 0) {
            $this->info('All beneficiaries already have activity records.');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$total} beneficiaries without activity records.");
        
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();
        
        $created = 0;
        $errors = [];
        
        foreach ($beneficiariesWithoutActivities as $beneficiary) {
            try {
                $activityData = $this->prepareActivityData($beneficiary);
                Activity::create($activityData);
                $created++;
            } catch (\Exception $e) {
                $errors[] = "Beneficiary ID {$beneficiary->id}: " . $e->getMessage();
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("Completed! Created {$created} activity records.");
        
        if (!empty($errors)) {
            $this->warn("Errors encountered:");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error($error);
            }
            if (count($errors) > 10) {
                $this->warn("... and " . (count($errors) - 10) . " more errors");
            }
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Prepare activity data from beneficiary
     */
    protected function prepareActivityData(Beneficiary $beneficiary): array
    {
        return [
            'beneficiary_id' => $beneficiary->id,
            'anc1_attended' => $beneficiary->anc1_attended ?? false,
            'anc1_date' => $beneficiary->anc1_date,
            'anc1_planned_date' => $beneficiary->anc1_planned_date,
            'anc2_attended' => $beneficiary->anc2_attended ?? false,
            'anc2_date' => $beneficiary->anc2_date,
            'anc2_planned_date' => $beneficiary->anc2_planned_date,
            'anc3_attended' => $beneficiary->anc3_attended ?? false,
            'anc3_date' => $beneficiary->anc3_date,
            'anc3_planned_date' => $beneficiary->anc3_planned_date,
            'anc4_attended' => $beneficiary->anc4_attended ?? false,
            'anc4_date' => $beneficiary->anc4_date,
            'anc4_planned_date' => $beneficiary->anc4_planned_date,
            'skilled_delivery_attended' => $beneficiary->skilled_delivery_attended ?? false,
            'delivery_date' => $beneficiary->delivery_date,
            'delivery_planned_date' => $beneficiary->delivery_planned_date,
            'post_natal_attended' => $beneficiary->post_natal_attended ?? false,
            'post_natal_date' => $beneficiary->post_natal_date,
            'post_natal_planned_date' => $beneficiary->post_natal_planned_date,
            'immunization_attended' => $beneficiary->immunization_attended ?? false,
            'immunization_date' => $beneficiary->immunization_date,
            'immunization_planned_date' => $beneficiary->immunization_planned_date,
            'family_planning_attended' => $beneficiary->family_planning_attended ?? false,
            'family_planning_date' => $beneficiary->family_planning_date,
            'referred_to_hospital' => $beneficiary->referred_to_hospital ?? false,
            'referral_date' => $beneficiary->referral_date,
            'referral_stage' => $beneficiary->referral_stage,
            'referred_hospital_id' => $beneficiary->referred_hospital_id,
            'child_file_number' => $beneficiary->child_file_number,
            'payment_method' => $beneficiary->payment_method,
            'status' => $beneficiary->status ?? 'active',
            'exit_reason' => $beneficiary->exit_reason,
            'exit_date' => $beneficiary->exit_date,
            'expiry_date' => $beneficiary->expiry_date,
            'completion_date' => $beneficiary->completion_date,
            'field_officer_name' => $beneficiary->field_officer_name,
            'data_entry_month' => $beneficiary->data_entry_month,
            'date_sent_to_piu' => $beneficiary->date_sent_to_piu,
        ];
    }
}
