<?php

use Database\Seeders\MembershipWorkflowEmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->text('payment_instructions')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->date('payment_processed_at')->nullable();
        });

        (new MembershipWorkflowEmailTemplateSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['payment_instructions', 'payment_submitted_at', 'payment_processed_at']);
        });
    }
};
