<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'beneficiary_id',
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
        'payment_method',
        'status', 'exit_reason', 'exit_date', 'expiry_date', 'completion_date',
        'field_officer_name', 'data_entry_month', 'date_sent_to_piu',
    ];

    protected $casts = [
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
        'exit_date' => 'date',
        'expiry_date' => 'date',
        'completion_date' => 'date',
        'date_sent_to_piu' => 'date',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function referredHospital(): BelongsTo
    {
        return $this->belongsTo(GeneralHospital::class, 'referred_hospital_id');
    }
}


