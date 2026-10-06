<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separates "admin approved the documents" from "membership is live".
 *
 *   admin_approved_at     — timestamp when the admin clicked Approve. Stored
 *                            even if payment wasn't verified yet.
 *   admin_approved_until  — the expiry date the admin picked at that time;
 *                            applied to members.active_until when the
 *                            application eventually becomes fully Approved.
 *
 * When payment is later verified, if admin_approved_at is set we auto-
 * finalise the approval (status → Approved, member activated, approval email
 * sent) without needing a second admin click.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->timestamp('admin_approved_at')->nullable()->after('rejection_reason');
            $table->date('admin_approved_until')->nullable()->after('admin_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['admin_approved_at', 'admin_approved_until']);
        });
    }
};
