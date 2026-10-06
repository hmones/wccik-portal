<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->string('membership_number')->unique();
            $table->string('membership_class')->nullable();
            $table->string('authorized_representative_name');
            $table->string('company_name');
            $table->string('email');
            $table->string('website')->nullable();
            $table->smallInteger('established_year')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_classification')->nullable();
            $table->string('cnic');
            $table->date('cnic_expiry_date')->nullable();
            $table->unsignedBigInteger('turnover_pkr')->nullable();
            $table->unsignedInteger('employees_count')->nullable();
            $table->string('ntn_number')->nullable();
            $table->string('sales_tax_no')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('district')->nullable();
            $table->string('phone')->nullable();
            $table->string('cell');
            $table->string('whatsapp')->nullable();
            $table->string('alternate_no')->nullable();
            $table->text('other_chamber_memberships')->nullable();
            $table->timestamps();

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
