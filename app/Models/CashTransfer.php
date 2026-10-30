<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransfer extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'schedule_reference',
        'anc1_amount',
        'anc2_3_amount',
        'anc4_amount',
        'skilled_delivery_amount',
        'post_natal_immunization_amount',
        'total_entitled',
        'previous_disbursed',
        'current_disbursement',
        'balance',
        'status',
        'scheduled_date',
        'disbursed_date',
        'bank_name',
        'transaction_reference',
        'notes',
        'verified_by',
        'approved_by',
        'verified_at',
        'approved_at',
    ];

    protected $casts = [
        'anc1_amount' => 'decimal:2',
        'anc2_3_amount' => 'decimal:2',
        'anc4_amount' => 'decimal:2',
        'skilled_delivery_amount' => 'decimal:2',
        'post_natal_immunization_amount' => 'decimal:2',
        'total_entitled' => 'decimal:2',
        'previous_disbursed' => 'decimal:2',
        'current_disbursement' => 'decimal:2',
        'balance' => 'decimal:2',
        'scheduled_date' => 'date',
        'disbursed_date' => 'date',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Calculate current disbursement
    public function calculateDisbursement(): void
    {
        $this->current_disbursement = $this->total_entitled - $this->previous_disbursed;
        $this->balance = 0;
        $this->save();
    }
}
