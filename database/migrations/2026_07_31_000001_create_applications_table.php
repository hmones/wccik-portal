<?php

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('status_token')->unique();

            // Application meta
            $table->string('type');    // ApplicationType enum
            $table->string('status')->default(ApplicationStatus::Submitted->value);

            // Applicant, shared between new member and renewal
            $table->string('authorized_representative_name');
            $table->string('email');
            $table->string('cnic');
            $table->string('cell');
            $table->string('whatsapp')->nullable();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_classification')->nullable();  // CompanyClassification enum
            $table->text('address')->nullable();
            $table->string('district')->nullable();

            // NTN, new member only
            $table->boolean('has_ntn')->nullable();
            $table->string('ntn_number')->nullable();
            $table->string('ntn_reason')->nullable();

            // Renewal-specific
            $table->string('existing_membership_number')->nullable();
            $table->string('payment_proof_path')->nullable();

            // Admin review
            $table->boolean('physical_form_received')->default(false);
            $table->boolean('documents_received')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->string('membership_id')->nullable()->unique();

            // Payment, manual in Phase 1
            $table->string('payment_method')->nullable();          // PaymentMethod enum
            $table->boolean('payment_verified')->default(false);
            $table->date('payment_date')->nullable();
            $table->text('payment_notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
