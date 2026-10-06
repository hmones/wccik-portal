<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->foreignId('applicant_id')->nullable()->after('status_token')->constrained()->nullOnDelete();
            $table->timestamp('terms_confirmed_at')->nullable()->after('submitted_at');

            $table->index(['applicant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropForeign(['applicant_id']);
            $table->dropIndex(['applicant_id', 'status']);
            $table->dropColumn(['applicant_id', 'terms_confirmed_at']);
        });
    }
};
