<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phc extends Model
{
    protected $fillable = [
        'state_id',
        'lga_id',
        'ward_id',
        'general_hospital_id',
        'name',
        'code',
        'address',
        'phone',
        'cluster',
        'total_beneficiaries',
        'active_beneficiaries',
        'exited_beneficiaries',
        'total_cash_disbursed',
        'is_active',
    ];

    protected $casts = [
        'total_cash_disbursed' => 'decimal:2',
        'is_active' => 'boolean',
    ];

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

    public function generalHospital(): BelongsTo
    {
        return $this->belongsTo(GeneralHospital::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
