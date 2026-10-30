<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = [
        'zone_id',
        'name',
        'code',
        'registration_prefix',
        'partner_bank',
        'total_beneficiaries',
        'total_cash_disbursed',
        'phc_count',
        'active_beneficiaries',
        'exited_beneficiaries',
    ];

    protected $casts = [
        'total_cash_disbursed' => 'decimal:2',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function lgas(): HasMany
    {
        return $this->hasMany(Lga::class);
    }

    public function phcs(): HasMany
    {
        return $this->hasMany(Phc::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function generalHospitals(): HasMany
    {
        return $this->hasMany(GeneralHospital::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Helper methods for dashboard statistics
    public function getActivityLevel(): string
    {
        if ($this->total_beneficiaries == 0) return 'none';
        if ($this->total_beneficiaries < 50) return 'low';
        if ($this->total_beneficiaries < 200) return 'medium';
        return 'high';
    }

    public function getHeatmapColor(): string
    {
        // Assign colors based on zones for visual variety
        $zoneName = $this->zone?->name ?? '';
        
        // Define color palettes for each zone
        $zoneColorPalettes = [
            'North Central' => ['#FF6B6B', '#FF8E8E', '#FFA5A5', '#FFB6B6', '#FFC7C7', '#FFD8D8', '#FFE9E9'],
            'North East' => ['#4ECDC4', '#6FD9D1', '#8FE0D9', '#AFE7E1', '#CFEEE9', '#DFF5F1'],
            'North West' => ['#95E1D3', '#A8E6DA', '#BBEAE1', '#CEEFE8', '#E1F4EF', '#F4F9F7'],
            'South East' => ['#F38181', '#F59595', '#F7A9A9', '#F9BDBD', '#FBD1D1', '#FDE5E5'],
            'South South' => ['#AA96DA', '#B9A8E0', '#C8BAE6', '#D7CCEC', '#E6DEF2', '#F5F0F8'],
            'South West' => ['#FCBAD3', '#FDC7DC', '#FDD4E5', '#FEE1EE', '#FEEEF7', '#FFFBFD'],
        ];

        $palette = $zoneColorPalettes[$zoneName] ?? ['#E0E0E0'];
        
        // Use state ID to pick a color from the palette
        $colorIndex = ($this->id - 1) % count($palette);
        
        return $palette[$colorIndex];
    }
}
