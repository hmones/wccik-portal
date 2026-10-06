<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('membership_class')->nullable()->after('type');
            $table->string('industry')->nullable()->after('company_classification');
            $table->string('website')->nullable()->after('industry');
            $table->smallInteger('established_year')->nullable()->after('website');
            $table->date('cnic_expiry_date')->nullable()->after('cnic');
            $table->unsignedBigInteger('turnover_pkr')->nullable()->after('cnic_expiry_date');
            $table->unsignedInteger('employees_count')->nullable()->after('turnover_pkr');
            $table->string('sales_tax_no')->nullable()->after('ntn_number');
            $table->string('postal_code', 10)->nullable()->after('district');
            $table->string('alternate_no')->nullable()->after('whatsapp');
            $table->text('other_chamber_memberships')->nullable()->after('alternate_no');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn([
                'membership_class',
                'industry',
                'website',
                'established_year',
                'cnic_expiry_date',
                'turnover_pkr',
                'employees_count',
                'sales_tax_no',
                'postal_code',
                'alternate_no',
                'other_chamber_memberships',
            ]);
        });
    }
};
