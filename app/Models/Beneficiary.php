<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Beneficiary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'phc_id', 'state_id', 'lga_id', 'ward_id',
        'registration_number', 'date_registered', 'enrollment_date',
        'surname', 'first_name', 'other_names', 'age', 'date_of_birth',
        'address', 'village_ward', 'phone_number', 'phone_owned_by', 'payment_method',
        'nok_name', 'nok_phone', 'nok_address', 'nok_relationship',
        'anc1_attended', 'anc1_date', 'anc1_planned_date',
        'anc2_attended', 'anc2_date', 'anc2_planned_date',
        'anc3_attended', 'anc3_date', 'anc3_planned_date',
        'anc4_attended', 'anc4_date', 'anc4_planned_date',
        'skilled_delivery_attended', 'delivery_date', 'delivery_planned_date',
        'post_natal_attended', 'post_natal_date', 'post_natal_planned_date',
        'immunization_attended', 'immunization_date', 'immunization_planned_date',
        'family_planning_attended', 'family_planning_date',
        'referred_to_hospital', 'referral_date', 'referral_stage', 'referred_hospital_id',
        'child_file_number',
        'total_entitled_amount', 'total_disbursed_amount', 'pending_amount',
        'status', 'exit_reason', 'exit_date', 'expiry_date', 'completion_date',
        'last_attendance_date', 'next_planned_attendance',
        'is_potential_defaulter', 'is_flagged', 'flagged_reason',
        'field_officer_name', 'data_entry_month', 'date_sent_to_piu',
    ];

    protected $casts = [
        'date_registered' => 'date',
        'enrollment_date' => 'date',
        'date_of_birth' => 'date',
        'anc1_attended' => 'boolean',
        'anc1_date' => 'date',
        'anc1_planned_date' => 'date',
        'anc2_attended' => 'boolean',
        'anc2_date' => 'date',
        'anc2_planned_date' => 'date',
        'anc3_attended' => 'boolean',
        'anc3_date' => 'date',
        'anc3_planned_date' => 'date',
        'anc4_attended' => 'boolean',
        'anc4_date' => 'date',
        'anc4_planned_date' => 'date',
        'skilled_delivery_attended' => 'boolean',
        'delivery_date' => 'date',
        'delivery_planned_date' => 'date',
        'post_natal_attended' => 'boolean',
        'post_natal_date' => 'date',
        'post_natal_planned_date' => 'date',
        'immunization_attended' => 'boolean',
        'immunization_date' => 'date',
        'immunization_planned_date' => 'date',
        'family_planning_attended' => 'boolean',
        'family_planning_date' => 'date',
        'referred_to_hospital' => 'boolean',
        'referral_date' => 'date',
        'total_entitled_amount' => 'decimal:2',
        'total_disbursed_amount' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'exit_date' => 'date',
        'expiry_date' => 'date',
        'completion_date' => 'date',
        'last_attendance_date' => 'date',
        'next_planned_attendance' => 'date',
        'is_potential_defaulter' => 'boolean',
        'is_flagged' => 'boolean',
        'date_sent_to_piu' => 'date',
    ];

    // Relationships
    public function phc(): BelongsTo
    {
        return $this->belongsTo(Phc::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function referredHospital(): BelongsTo
    {
        return $this->belongsTo(GeneralHospital::class, 'referred_hospital_id');
    }

    public function cashTransfers(): HasMany
    {
        return $this->hasMany(CashTransfer::class);
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        $name = $this->surname . ' ' . $this->first_name;
        if ($this->other_names) {
            $name .= ' ' . $this->other_names;
        }
        return $name;
    }

    public function calculateEntitlement(): float
    {
        $amount = 0;

        // ANC1 + Registration = N1000
        if ($this->anc1_attended) {
            $amount += 1000;
        }

        // ANC2 & ANC3 = Up to N1000 (pro-rated)
        $anc2_3_attended = 0;
        if ($this->anc2_attended) $anc2_3_attended++;
        if ($this->anc3_attended) $anc2_3_attended++;
        $amount += ($anc2_3_attended * 500); // 500 per visit

        // ANC4 = N300 for one, N600 for two attendances
        if ($this->anc4_attended) {
            $amount += 600; // Assuming full attendance for now
        }

        // Skilled Delivery = N2000
        if ($this->skilled_delivery_attended) {
            $amount += 2000;
        }

        // Post Natal OR Immunization = N1000 (either qualifies)
        if ($this->post_natal_attended || $this->immunization_attended) {
            $amount += 1000;
        }

        return $amount;
    }

    public function checkForExpiry(): void
    {
        if ($this->status === 'active' && $this->expiry_date && Carbon::now()->greaterThan($this->expiry_date)) {
            $this->status = 'expired';
            $this->exit_date = Carbon::now();
            $this->save();
        }
    }

    public function checkForDefaulter(): void
    {
        if ($this->status !== 'active') {
            $this->is_potential_defaulter = false;
            $this->save();
            return;
        }

        $hasPlannedMissed = false;
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Check if missed planned appointment
        if ($this->next_planned_attendance && Carbon::now()->greaterThan($this->next_planned_attendance)) {
            $hasPlannedMissed = true;
        }

        // Check if no attendance for 30 days
        if ($this->last_attendance_date && $this->last_attendance_date->lessThan($thirtyDaysAgo)) {
            $hasPlannedMissed = true;
        }

        $this->is_potential_defaulter = $hasPlannedMissed;
        $this->save();
    }

    // Boot method to auto-calculate expiry date
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($beneficiary) {
            if (!$beneficiary->expiry_date && $beneficiary->date_registered) {
                $beneficiary->expiry_date = Carbon::parse($beneficiary->date_registered)->addMonths(9);
            }
        });
    }
}
