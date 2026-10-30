<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\Activity;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ImportBeneficiariesFromExcel2013 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beneficiaries:import2013 
                            {file : Path to the Excel file}
                            {--phc_id=1 : PHC ID}
                            {--state_id=7 : State ID}
                            {--lga_id=453 : LGA ID}
                            {--preview : Preview first 5 rows without importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import beneficiaries from Excel file (2013 format - CCT Beneficiary Log sheet)';

    /**
     * Column mapping from Excel to database
     */
    protected $columnMapping = [
        // Personal Information
        'registration_number' => ['Registration Number', 'Reg No', 'Reg Number', 'Registration No', 'REG. CARD NO', 'REG CARD NO', 'Reg Card No'],
        'surname' => ['Surname', 'Last Name', 'Lastname', 'BENEFICIARY SURNAME', 'Beneficiary Surname'],
        'first_name' => ['First Name', 'Firstname', 'Given Name', 'FIRST NAME'],
        'other_names' => ['Other Names', 'Middle Name', 'Middle Names', 'Other Name'],
        'age' => ['Age', 'AGE'],
        'date_of_birth' => ['Date of Birth', 'DOB', 'Birth Date', 'Birthdate'],
        'address' => ['Address', 'Residential Address', 'ADDRESS/VILLAGE', 'ADDRESS', 'Address/Village'],
        'village_ward' => ['Village/Ward', 'Village', 'Ward', 'Village Ward'],
        'phone_number' => ['Phone Number', 'Phone', 'Mobile', 'Mobile Number', 'Tel', 'CONTACT PHONE NO', 'Contact Phone No'],
        'phone_owned_by' => ['Phone Owned By', 'Phone Owner', 'PHONE OWNED BY'],
        'payment_method' => ['Payment Method', 'Payment Mode', 'PAYMENT METHOD', 'Payment Method'],
        
        // Next of Kin
        'nok_name' => ['Next of Kin Name', 'NOK Name', 'Next of Kin'],
        'nok_phone' => ['Next of Kin Phone', 'NOK Phone', 'NOK Mobile'],
        'nok_address' => ['Next of Kin Address', 'NOK Address'],
        'nok_relationship' => ['NOK Relationship', 'Relationship'],
        
        // Dates
        'date_registered' => ['Date Registered', 'Registration Date', 'Date Reg', 'DATE REGISTERED'],
        'enrollment_date' => ['Enrollment Date', 'Enrolment Date'],
        
        // ANC Visits
        'anc1_attended' => ['ANC1 Attended', 'ANC 1 Attended', 'ANC1', 'REG / ANC 1', 'REG/ANC 1', 'REG / ANC1'],
        'anc1_date' => ['ANC1 Date', 'ANC 1 Date', 'DATE ANC1', 'Date ANC1'],
        'anc1_planned_date' => ['ANC1 Planned Date', 'ANC 1 Planned'],
        'anc2_attended' => ['ANC2 Attended', 'ANC 2 Attended', 'ANC2', 'ANC 2'],
        'anc2_date' => ['ANC2 Date', 'ANC 2 Date', 'DATE ANC2', 'Date ANC2'],
        'anc2_planned_date' => ['ANC2 Planned Date', 'ANC 2 Planned'],
        'anc3_attended' => ['ANC3 Attended', 'ANC 3 Attended', 'ANC3', 'ANC 3'],
        'anc3_date' => ['ANC3 Date', 'ANC 3 Date', 'DATE ANC3', 'Date ANC3'],
        'anc3_planned_date' => ['ANC3 Planned Date', 'ANC 3 Planned'],
        'anc4_attended' => ['ANC4 Attended', 'ANC 4 Attended', 'ANC4', 'ANC 4'],
        'anc4_date' => ['ANC4 Date', 'ANC 4 Date', 'DATE ANC4', 'Date ANC4'],
        'anc4_planned_date' => ['ANC4 Planned Date', 'ANC 4 Planned'],
        
        // Delivery
        'skilled_delivery_attended' => ['Skilled Delivery', 'Delivery Attended', 'Delivery', 'SKILLED DELIVERY'],
        'delivery_date' => ['Delivery Date', 'DATE DELIVERY', 'Date Delivery'],
        'delivery_planned_date' => ['Delivery Planned Date'],
        
        // Post Natal
        'post_natal_attended' => ['Post Natal Attended', 'PNC Attended', 'Post Natal', 'POST NATAL', 'PNC'],
        'post_natal_date' => ['Post Natal Date', 'PNC Date', 'DATE PNC', 'Date PNC'],
        'post_natal_planned_date' => ['Post Natal Planned Date', 'PNC Planned'],
        
        // Immunization
        'immunization_attended' => ['Immunization Attended', 'Immunization', 'IMMUNIZATION'],
        'immunization_date' => ['Immunization Date', 'DATE IMM', 'Date IMM'],
        'immunization_planned_date' => ['Immunization Planned Date'],
        
        // Family Planning
        'family_planning_attended' => ['Family Planning Attended', 'Family Planning', 'FAMILY PLANNING ADVICE', 'FAMILY PLANNING'],
        'family_planning_date' => ['Family Planning Date', 'DATE FPA', 'Date FPA'],
        
        // Referral
        'referred_to_hospital' => ['Referred to Hospital', 'Referred', 'Referral'],
        'referral_date' => ['Referral Date', 'REFERRAL DATE (if applicable)', 'REFERRAL DATE', 'Referral Date (if applicable)'],
        'referral_stage' => ['Referral Stage'],
        
        // Financial
        'total_entitled_amount' => ['Total Entitled', 'Entitled Amount', 'Total Entitlement'],
        'total_disbursed_amount' => ['Total Disbursed', 'Disbursed Amount'],
        'pending_amount' => ['Pending Amount', 'Pending'],
        
        // Status
        'status' => ['Status'],
        'exit_reason' => ['Exit Reason'],
        'exit_date' => ['Exit Date'],
        'child_file_number' => ['Child File Number', 'File Number'],
        'field_officer_name' => ['Field Officer', 'Field Officer Name'],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $phcId = $this->option('phc_id');
        $stateId = $this->option('state_id');
        $lgaId = $this->option('lga_id');
        $preview = $this->option('preview');

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        try {
            $this->info("Loading Excel file: {$filePath}");
            $spreadsheet = IOFactory::load($filePath);
            
            // For 2013 format, data is ONLY in "CCT Beneficiary Log" sheet
            $sheetName = 'CCT Beneficiary Log';
            if (!$spreadsheet->sheetNameExists($sheetName)) {
                $this->error("Sheet '{$sheetName}' not found in file. Available sheets: " . implode(', ', $spreadsheet->getSheetNames()));
                return Command::FAILURE;
            }
            
            $worksheet = $spreadsheet->getSheetByName($sheetName);
            if (!$worksheet) {
                $this->error("Failed to load sheet '{$sheetName}'");
                return Command::FAILURE;
            }
            
            // Verify we're using the correct sheet by checking for expected header
            $expectedHeader = $worksheet->getCell('E19')->getValue(); // Should be "BENEFICIARY SURNAME"
            if (stripos($expectedHeader ?? '', 'BENEFICIARY SURNAME') === false && stripos($expectedHeader ?? '', 'SURNAME') === false) {
                $this->error("Sheet '{$sheetName}' does not contain expected headers. Found: " . ($expectedHeader ?? 'empty'));
                $this->error("Please ensure you are using the 'CCT Beneficiary Log' sheet.");
                return Command::FAILURE;
            }
            
            $this->info("Using sheet: {$sheetName} (verified)");
            
            // For 2013 format, header row is 19
            $headerRow = 19;
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            $this->info("Using row {$headerRow} as header row");
            
            // Get headers from row 19
            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, $headerRow)->getValue();
                $headers[$col] = trim($cellValue ?? '');
            }
            
            $this->info("Found " . count($headers) . " columns");
            
            // Map Excel columns to database fields
            $columnMap = [];
            foreach ($headers as $colIndex => $headerName) {
                if (empty($headerName)) continue;
                
                // Clean header name for matching
                $cleanHeader = trim(strtoupper($headerName));
                
                foreach ($this->columnMapping as $dbField => $excelNames) {
                    foreach ($excelNames as $excelName) {
                        $cleanExcelName = trim(strtoupper($excelName));
                        // Try exact match first, then partial match
                        if ($cleanHeader === $cleanExcelName || stripos($cleanHeader, $cleanExcelName) !== false) {
                            // Don't overwrite if already mapped (prefer exact matches)
                            if (!isset($columnMap[$dbField])) {
                                $columnMap[$dbField] = $colIndex;
                            }
                            break;
                        }
                    }
                }
            }
            
            // Show found headers for debugging
            $nonEmptyHeaders = array_filter($headers, fn($h) => !empty($h));
            if (!empty($nonEmptyHeaders)) {
                $this->info("Sample headers found:");
                $sampleHeaders = array_slice($nonEmptyHeaders, 0, 10, true);
                foreach ($sampleHeaders as $idx => $name) {
                    $this->line("  Column {$idx}: {$name}");
                }
            }
            
            if (empty($columnMap)) {
                $this->warn("No columns matched. Showing all non-empty headers:");
                $headerTable = [];
                foreach ($headers as $idx => $name) {
                    if (!empty($name)) {
                        $headerTable[] = [$idx, $name];
                    }
                }
                if (!empty($headerTable)) {
                    $this->table(['Column Index', 'Header Name'], $headerTable);
                } else {
                    $this->error("No headers found in row {$headerRow}");
                }
                return Command::FAILURE;
            }
            
            $this->info("Mapped " . count($columnMap) . " columns");
            
            // Get data rows (start from row 21 for 2013 format)
            $dataStartRow = 21;
            $totalRows = $highestRow - $dataStartRow + 1;
            
            $this->info("Found {$totalRows} data rows");
            
            if ($preview) {
                $this->info("\n=== PREVIEW MODE (First 5 rows) ===");
                $previewRows = min(5, $totalRows);
            } else {
                $previewRows = 0;
            }
            
            $inserted = 0;
            $skipped = 0;
            $activitiesCreated = 0;
            $errors = [];
            $seenInThisImport = []; // Track duplicates within the same import run
            
            $progressBar = $preview ? null : $this->output->createProgressBar($totalRows);
            if ($progressBar) {
                $progressBar->start();
            }
            
            for ($row = $dataStartRow; $row <= $highestRow; $row++) {
                if ($preview && $row > $dataStartRow + 4) {
                    break;
                }
                
                $rowData = [];
                foreach ($columnMap as $dbField => $colIndex) {
                    $cellValue = $worksheet->getCellByColumnAndRow($colIndex, $row)->getValue();
                    $rowData[$dbField] = $cellValue;
                }
                
                // Skip empty rows
                if (empty($rowData['surname']) && empty($rowData['first_name'])) {
                    $skipped++;
                    if ($progressBar) $progressBar->advance();
                    continue;
                }
                
                if ($preview) {
                    $this->line("\nRow {$row}:");
                    foreach ($rowData as $key => $value) {
                        if (!empty($value)) {
                            $this->line("  {$key}: " . (is_scalar($value) ? $value : json_encode($value)));
                        }
                    }
                    continue;
                }
                
                try {
                    $beneficiaryData = $this->prepareBeneficiaryData($rowData, $phcId, $stateId, $lgaId);
                    
                    // Skip if no registration number
                    if (empty($beneficiaryData['registration_number'])) {
                        $skipped++;
                        if ($progressBar) $progressBar->advance();
                        continue;
                    }
                    
                    // Allow null values for surname and first_name (columns are now nullable)
                    
                    // Create a unique key for duplicate detection within this import (using registration_number only)
                    $duplicateKey = $beneficiaryData['registration_number'];
                    
                    // Check if we've already seen this registration_number in this import run
                    if (isset($seenInThisImport[$duplicateKey])) {
                        $skipped++;
                        if ($progressBar) $progressBar->advance();
                        continue;
                    }
                    
                    // Check for duplicates in database - check by registration_number only
                    $existing = Beneficiary::where('registration_number', $beneficiaryData['registration_number'])->first();
                    
                    if ($existing) {
                        // Beneficiary already exists - always create a fresh activity record
                        $beneficiary = $existing;
                        
                        try {
                            $activityData = $this->prepareActivityData($beneficiaryData, $beneficiary->id);
                            
                            // Always create a new activity record (beneficiaries can have multiple activities)
                            Activity::create($activityData);
                            $activitiesCreated++;
                        } catch (\Exception $e) {
                            $errors[] = "Row {$row} (duplicate beneficiary): Activity creation failed - " . $e->getMessage();
                        }
                        
                        $skipped++; // Count as skipped for beneficiary, but activity was created
                        if ($progressBar) $progressBar->advance();
                        continue;
                    }
                    
                    // Mark as seen in this import run
                    $seenInThisImport[$duplicateKey] = true;
                    
                    // Create new beneficiary
                    $beneficiary = Beneficiary::create($beneficiaryData);
                    
                    // Create corresponding activity record
                    try {
                        $activityData = $this->prepareActivityData($beneficiaryData, $beneficiary->id);
                        Activity::create($activityData);
                        $activitiesCreated++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$row}: Activity creation failed - " . $e->getMessage();
                    }
                    
                    $inserted++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    $skipped++;
                }
                
                if ($progressBar) $progressBar->advance();
            }
            
            if ($progressBar) {
                $progressBar->finish();
                $this->newLine();
            }
            
            $this->newLine();
            $this->info("Import completed!");
            $this->info("Beneficiaries inserted: {$inserted}");
            $this->info("Beneficiaries skipped: {$skipped}");
            $this->info("Activities created: {$activitiesCreated}");
            
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
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
    
    /**
     * Prepare beneficiary data for insertion
     */
    protected function prepareBeneficiaryData(array $rowData, int $phcId, int $stateId, int $lgaId): array
    {
        $data = [
            'phc_id' => $phcId,
            'state_id' => $stateId,
            'lga_id' => $lgaId,
            'surname' => $this->cleanString($rowData['surname'] ?? ''),
            'first_name' => $this->cleanString($rowData['first_name'] ?? ''),
            'other_names' => $this->cleanString($rowData['other_names'] ?? null),
            'registration_number' => $this->cleanString($rowData['registration_number'] ?? null),
            'age' => $this->parseInt($rowData['age'] ?? null),
            'address' => $this->cleanString($rowData['address'] ?? null),
            'village_ward' => $this->cleanString($rowData['village_ward'] ?? null),
            'phone_number' => $this->cleanString($rowData['phone_number'] ?? null),
            'phone_owned_by' => $this->cleanString($rowData['phone_owned_by'] ?? null),
            'payment_method' => $this->cleanString($rowData['payment_method'] ?? null) ?? 'Cashier Payout',
            'nok_name' => $this->cleanString($rowData['nok_name'] ?? null),
            'nok_phone' => $this->cleanString($rowData['nok_phone'] ?? null),
            'nok_address' => $this->cleanString($rowData['nok_address'] ?? null),
            'nok_relationship' => $this->cleanString($rowData['nok_relationship'] ?? null),
            'child_file_number' => $this->cleanString($rowData['child_file_number'] ?? null),
            'field_officer_name' => $this->cleanString($rowData['field_officer_name'] ?? null),
            'status' => $this->cleanString($rowData['status'] ?? 'active'),
            'exit_reason' => $this->cleanString($rowData['exit_reason'] ?? null),
            'referral_stage' => $this->cleanString($rowData['referral_stage'] ?? null),
        ];
        
        // Parse dates - handle ANC attended fields that might contain dates
        $data['date_of_birth'] = $this->parseDate($rowData['date_of_birth'] ?? null);
        $data['date_registered'] = $this->parseDate($rowData['date_registered'] ?? null);
        $data['enrollment_date'] = $this->parseDate($rowData['enrollment_date'] ?? null);
        
        // ANC dates - check if attended field contains a date
        $anc1AttendedValue = $rowData['anc1_attended'] ?? null;
        $anc1DateValue = $rowData['anc1_date'] ?? null;
        $anc1ParsedDate = $this->parseDate($anc1DateValue);
        if (!$anc1ParsedDate && $anc1AttendedValue) {
            $anc1ParsedDate = $this->parseDate($anc1AttendedValue);
        }
        $data['anc1_date'] = $anc1ParsedDate;
        $data['anc1_attended'] = !empty($anc1ParsedDate) || $this->parseBoolean($anc1AttendedValue);
        $data['anc1_planned_date'] = $this->parseDate($rowData['anc1_planned_date'] ?? null);
        
        $anc2AttendedValue = $rowData['anc2_attended'] ?? null;
        $anc2DateValue = $rowData['anc2_date'] ?? null;
        $anc2ParsedDate = $this->parseDate($anc2DateValue);
        if (!$anc2ParsedDate && $anc2AttendedValue) {
            $anc2ParsedDate = $this->parseDate($anc2AttendedValue);
        }
        $data['anc2_date'] = $anc2ParsedDate;
        $data['anc2_attended'] = !empty($anc2ParsedDate) || $this->parseBoolean($anc2AttendedValue);
        $data['anc2_planned_date'] = $this->parseDate($rowData['anc2_planned_date'] ?? null);
        
        $anc3AttendedValue = $rowData['anc3_attended'] ?? null;
        $anc3DateValue = $rowData['anc3_date'] ?? null;
        $anc3ParsedDate = $this->parseDate($anc3DateValue);
        if (!$anc3ParsedDate && $anc3AttendedValue) {
            $anc3ParsedDate = $this->parseDate($anc3AttendedValue);
        }
        $data['anc3_date'] = $anc3ParsedDate;
        $data['anc3_attended'] = !empty($anc3ParsedDate) || $this->parseBoolean($anc3AttendedValue);
        $data['anc3_planned_date'] = $this->parseDate($rowData['anc3_planned_date'] ?? null);
        
        $anc4AttendedValue = $rowData['anc4_attended'] ?? null;
        $anc4DateValue = $rowData['anc4_date'] ?? null;
        $anc4ParsedDate = $this->parseDate($anc4DateValue);
        if (!$anc4ParsedDate && $anc4AttendedValue) {
            $anc4ParsedDate = $this->parseDate($anc4AttendedValue);
        }
        $data['anc4_date'] = $anc4ParsedDate;
        $data['anc4_attended'] = !empty($anc4ParsedDate) || $this->parseBoolean($anc4AttendedValue);
        $data['anc4_planned_date'] = $this->parseDate($rowData['anc4_planned_date'] ?? null);
        
        $data['delivery_date'] = $this->parseDate($rowData['delivery_date'] ?? null);
        $data['delivery_planned_date'] = $this->parseDate($rowData['delivery_planned_date'] ?? null);
        $data['post_natal_date'] = $this->parseDate($rowData['post_natal_date'] ?? null);
        $data['post_natal_planned_date'] = $this->parseDate($rowData['post_natal_planned_date'] ?? null);
        $data['immunization_date'] = $this->parseDate($rowData['immunization_date'] ?? null);
        $data['immunization_planned_date'] = $this->parseDate($rowData['immunization_planned_date'] ?? null);
        $data['family_planning_date'] = $this->parseDate($rowData['family_planning_date'] ?? null);
        $data['referral_date'] = $this->parseDate($rowData['referral_date'] ?? null);
        $data['exit_date'] = $this->parseDate($rowData['exit_date'] ?? null);
        
        // Parse booleans for other fields
        $data['skilled_delivery_attended'] = !empty($data['delivery_date']) || $this->parseBoolean($rowData['skilled_delivery_attended'] ?? null);
        $data['post_natal_attended'] = !empty($data['post_natal_date']) || $this->parseBoolean($rowData['post_natal_attended'] ?? null);
        $data['immunization_attended'] = !empty($data['immunization_date']) || $this->parseBoolean($rowData['immunization_attended'] ?? null);
        $data['family_planning_attended'] = !empty($data['family_planning_date']) || $this->parseBoolean($rowData['family_planning_attended'] ?? null);
        $data['referred_to_hospital'] = !empty($data['referral_date']) || $this->parseBoolean($rowData['referred_to_hospital'] ?? null);
        
        // Parse decimals - set defaults to 0 if null
        $data['total_entitled_amount'] = $this->parseDecimal($rowData['total_entitled_amount'] ?? null) ?? 0;
        $data['total_disbursed_amount'] = $this->parseDecimal($rowData['total_disbursed_amount'] ?? null) ?? 0;
        $data['pending_amount'] = $this->parseDecimal($rowData['pending_amount'] ?? null);
        
        // Calculate pending if not provided
        if ($data['pending_amount'] === null) {
            $data['pending_amount'] = $data['total_entitled_amount'] - $data['total_disbursed_amount'];
        }
        
        // Ensure pending is not negative
        if ($data['pending_amount'] < 0) {
            $data['pending_amount'] = 0;
        }
        
        // Set default date_registered if not provided
        if (!$data['date_registered']) {
            $data['date_registered'] = Carbon::now();
        }
        
        return $data;
    }
    
    protected function cleanString($value)
    {
        if ($value === null || $value === '') return null;
        $cleaned = trim((string) $value);
        return $cleaned === '' ? null : $cleaned;
    }
    
    protected function parseInt($value)
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int) $value;
        return null;
    }
    
    protected function parseDecimal($value)
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float) $value;
        return null;
    }
    
    protected function parseBoolean($value)
    {
        if ($value === null || $value === '') return false;
        if (is_bool($value)) return $value;
        if (is_numeric($value)) {
            // If it's a number (could be Excel date serial), check if it's > 0
            return (float)$value > 0;
        }
        $value = strtolower(trim((string) $value));
        return in_array($value, ['yes', 'y', '1', 'true', 'attended', 'attended yes', 'attended?']);
    }
    
    protected function parseDate($value)
    {
        if ($value === null || $value === '') return null;
        
        // If it's already a Carbon instance or DateTime
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }
        
        // If it's an Excel date serial number
        if (is_numeric($value) && $value > 25569) { // Excel epoch starts at 1900-01-01
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            } catch (\Exception $e) {
                // Fall through to string parsing
            }
        }
        
        // Try common date formats first (DD/MM/YYYY is common in this data)
        $dateFormats = [
            'd/m/Y',      // 29/04/2013
            'd-m-Y',      // 29-04-2013
            'Y-m-d',      // 2013-04-29
            'Y/m/d',      // 2013/04/29
            'm/d/Y',      // 04/29/2013
            'd.m.Y',      // 29.04.2013
        ];
        
        foreach ($dateFormats as $format) {
            try {
                $date = Carbon::createFromFormat($format, (string)$value);
                if ($date) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Try to parse as string (fallback)
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Prepare activity data for insertion
     */
    protected function prepareActivityData(array $beneficiaryData, int $beneficiaryId): array
    {
        return [
            'beneficiary_id' => $beneficiaryId,
            'anc1_attended' => $beneficiaryData['anc1_attended'] ?? false,
            'anc1_date' => $beneficiaryData['anc1_date'] ?? null,
            'anc1_planned_date' => $beneficiaryData['anc1_planned_date'] ?? null,
            'anc2_attended' => $beneficiaryData['anc2_attended'] ?? false,
            'anc2_date' => $beneficiaryData['anc2_date'] ?? null,
            'anc2_planned_date' => $beneficiaryData['anc2_planned_date'] ?? null,
            'anc3_attended' => $beneficiaryData['anc3_attended'] ?? false,
            'anc3_date' => $beneficiaryData['anc3_date'] ?? null,
            'anc3_planned_date' => $beneficiaryData['anc3_planned_date'] ?? null,
            'anc4_attended' => $beneficiaryData['anc4_attended'] ?? false,
            'anc4_date' => $beneficiaryData['anc4_date'] ?? null,
            'anc4_planned_date' => $beneficiaryData['anc4_planned_date'] ?? null,
            'skilled_delivery_attended' => $beneficiaryData['skilled_delivery_attended'] ?? false,
            'delivery_date' => $beneficiaryData['delivery_date'] ?? null,
            'delivery_planned_date' => $beneficiaryData['delivery_planned_date'] ?? null,
            'post_natal_attended' => $beneficiaryData['post_natal_attended'] ?? false,
            'post_natal_date' => $beneficiaryData['post_natal_date'] ?? null,
            'post_natal_planned_date' => $beneficiaryData['post_natal_planned_date'] ?? null,
            'immunization_attended' => $beneficiaryData['immunization_attended'] ?? false,
            'immunization_date' => $beneficiaryData['immunization_date'] ?? null,
            'immunization_planned_date' => $beneficiaryData['immunization_planned_date'] ?? null,
            'family_planning_attended' => $beneficiaryData['family_planning_attended'] ?? false,
            'family_planning_date' => $beneficiaryData['family_planning_date'] ?? null,
            'referred_to_hospital' => $beneficiaryData['referred_to_hospital'] ?? false,
            'referral_date' => $beneficiaryData['referral_date'] ?? null,
            'referral_stage' => $beneficiaryData['referral_stage'] ?? null,
            'referred_hospital_id' => $beneficiaryData['referred_hospital_id'] ?? null,
            'child_file_number' => $beneficiaryData['child_file_number'] ?? null,
            'payment_method' => $beneficiaryData['payment_method'] ?? null,
            'status' => $beneficiaryData['status'] ?? 'active',
            'exit_reason' => $beneficiaryData['exit_reason'] ?? null,
            'exit_date' => $beneficiaryData['exit_date'] ?? null,
            'expiry_date' => $beneficiaryData['expiry_date'] ?? null,
            'completion_date' => $beneficiaryData['completion_date'] ?? null,
            'field_officer_name' => $beneficiaryData['field_officer_name'] ?? null,
            'data_entry_month' => $beneficiaryData['data_entry_month'] ?? null,
            'date_sent_to_piu' => $beneficiaryData['date_sent_to_piu'] ?? null,
        ];
    }
}
