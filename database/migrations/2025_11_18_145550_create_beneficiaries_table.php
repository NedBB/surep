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
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phc_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_id')->constrained()->onDelete('cascade');
            $table->foreignId('lga_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('ward_id')->nullable()->constrained()->onDelete('cascade');
            
            // Registration Details
            $table->string('registration_number')->unique();
            $table->date('date_registered');
            $table->date('enrollment_date')->nullable();
            
            // Personal Information
            $table->string('surname');
            $table->string('first_name');
            $table->string('other_names')->nullable();
            $table->integer('age')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('village_ward')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('phone_owned_by')->nullable();
            $table->string('payment_method')->default('Cashier Payout');
            
            // Next of Kin
            $table->string('nok_name')->nullable();
            $table->string('nok_phone')->nullable();
            $table->text('nok_address')->nullable();
            $table->string('nok_relationship')->nullable();
            
            // ANC Services
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
            $table->decimal('total_entitled_amount', 10, 2)->default(0);
            $table->decimal('total_disbursed_amount', 10, 2)->default(0);
            $table->decimal('pending_amount', 10, 2)->default(0);
            
            // Status and Completion
            $table->enum('status', ['active', 'completed', 'dropped_out', 'deceased', 'expired', 'unknown'])->default('active');
            $table->string('exit_reason')->nullable();
            $table->date('exit_date')->nullable();
            $table->date('expiry_date')->nullable(); // Automatically 9 months after registration
            $table->date('completion_date')->nullable();
            
            // Tracking
            $table->date('last_attendance_date')->nullable();
            $table->date('next_planned_attendance')->nullable();
            $table->boolean('is_potential_defaulter')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->text('flagged_reason')->nullable();
            
            // Data Entry Metadata
            $table->string('field_officer_name')->nullable();
            $table->string('data_entry_month')->nullable();
            $table->date('date_sent_to_piu')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('registration_number');
            $table->index('status');
            $table->index('phc_id');
            $table->index('state_id');
            $table->index(['date_registered', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
