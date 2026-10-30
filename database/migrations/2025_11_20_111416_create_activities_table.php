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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->onDelete('cascade');
            
            // ANC Activities
            $table->boolean('anc1_attended')->default(false);
            $table->date('anc1_date')->nullable();
            $table->date('anc1_planned_date')->nullable();
            
            $table->boolean('anc2_attended')->default(false);
            $table->date('anc2_date')->nullable();
            $table->date('anc2_planned_date')->nullable();
            
            $table->boolean('anc3_attended')->default(false);
            $table->date('anc3_date')->nullable();
            $table->date('anc3_planned_date')->nullable();
            
            $table->boolean('anc4_attended')->default(false);
            $table->date('anc4_date')->nullable();
            $table->date('anc4_planned_date')->nullable();
            
            // Delivery and Post-Natal
            $table->boolean('skilled_delivery_attended')->default(false);
            $table->date('delivery_date')->nullable();
            $table->date('delivery_planned_date')->nullable();
            
            $table->boolean('post_natal_attended')->default(false);
            $table->date('post_natal_date')->nullable();
            $table->date('post_natal_planned_date')->nullable();
            
            $table->boolean('immunization_attended')->default(false);
            $table->date('immunization_date')->nullable();
            $table->date('immunization_planned_date')->nullable();
            
            $table->boolean('family_planning_attended')->default(false);
            $table->date('family_planning_date')->nullable();
            
            // Referral Information
            $table->boolean('referred_to_hospital')->default(false);
            $table->date('referral_date')->nullable();
            $table->string('referral_stage')->nullable();
            $table->foreignId('referred_hospital_id')->nullable()->constrained('general_hospitals')->onDelete('set null');
            
            // Child Information
            $table->string('child_file_number')->nullable();
            
            // Financial Information
            $table->string('payment_method')->nullable();
            
            // Status and Completion
            $table->enum('status', ['active', 'completed', 'dropped_out', 'deceased', 'expired', 'unknown'])->default('active');
            $table->string('exit_reason')->nullable();
            $table->date('exit_date')->nullable();
            $table->date('expiry_date')->nullable(); // Automatically 9 months after registration
            $table->date('completion_date')->nullable();
          
            // Data Entry Metadata
            $table->string('field_officer_name')->nullable();
            $table->string('data_entry_month')->nullable();
            $table->date('date_sent_to_piu')->nullable();
            
            $table->timestamps();
            
            // Indexes for search and filtering
            $table->index('beneficiary_id');
            $table->index('status');
            $table->index('last_attendance_date');
            $table->index('next_planned_attendance');
            $table->index('is_potential_defaulter');
            $table->index('is_flagged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
