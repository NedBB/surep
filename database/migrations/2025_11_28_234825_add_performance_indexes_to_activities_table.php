<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Composite index for faster joins with beneficiaries on state_id filtering
            $table->index(['beneficiary_id', 'anc1_attended'], 'idx_beneficiary_anc1');
            $table->index(['beneficiary_id', 'anc2_attended'], 'idx_beneficiary_anc2');
            $table->index(['beneficiary_id', 'anc3_attended'], 'idx_beneficiary_anc3');
            $table->index(['beneficiary_id', 'anc4_attended'], 'idx_beneficiary_anc4');
            $table->index(['beneficiary_id', 'skilled_delivery_attended'], 'idx_beneficiary_delivery');
            $table->index(['beneficiary_id', 'post_natal_attended'], 'idx_beneficiary_postnatal');
            $table->index(['beneficiary_id', 'immunization_attended'], 'idx_beneficiary_immunization');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            // Composite index for faster state_id and status filtering
            $table->index(['state_id', 'status'], 'idx_state_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_beneficiary_anc1');
            $table->dropIndex('idx_beneficiary_anc2');
            $table->dropIndex('idx_beneficiary_anc3');
            $table->dropIndex('idx_beneficiary_anc4');
            $table->dropIndex('idx_beneficiary_delivery');
            $table->dropIndex('idx_beneficiary_postnatal');
            $table->dropIndex('idx_beneficiary_immunization');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex('idx_state_status');
        });
    }
};
