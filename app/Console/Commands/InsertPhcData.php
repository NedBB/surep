<?php

namespace App\Console\Commands;

use App\Models\Phc;
use Illuminate\Console\Command;

class InsertPhcData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phc:insert {--state_id=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert PHC data for a specific state';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stateId = $this->option('state_id');
        
        // PHC data: keys are PHC names, values are lga_id
        $phcData = [
            "Byazhin PHC" => 451,
            "Deidei PHC" => 451,
            "Dutse-Alhaji PHC" => 451,
            "Old-Deidei PHC" => 451,
        ];

        $this->info("Inserting PHC data for state_id: {$stateId}");

        $inserted = 0;
        $skipped = 0;

        foreach ($phcData as $phcName => $lgaId) {
            // Check if PHC already exists
            $existing = Phc::where('state_id', $stateId)
                ->where('lga_id', $lgaId)
                ->where('name', $phcName)
                ->first();

            if ($existing) {
                $this->warn("PHC '{$phcName}' already exists. Skipping...");
                $skipped++;
                continue;
            }

            // Create new PHC
            Phc::create([
                'state_id' => $stateId,
                'lga_id' => $lgaId,
                'name' => $phcName,
                'is_active' => true,
            ]);

            $this->info("✓ Inserted: {$phcName} (LGA ID: {$lgaId})");
            $inserted++;
        }

        $this->newLine();
        $this->info("Completed! Inserted: {$inserted}, Skipped: {$skipped}");
        
        return Command::SUCCESS;
    }
}
