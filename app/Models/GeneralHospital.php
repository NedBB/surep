<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneralHospital extends Model
{
    protected $fillable = [
        'state_id',
        'lga_id',
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
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

    public function phcs(): HasMany
    {
        return $this->hasMany(Phc::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Beneficiary::class, 'referred_hospital_id');
    }
}
