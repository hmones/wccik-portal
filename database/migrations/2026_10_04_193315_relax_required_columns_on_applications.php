<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drafts materialise on first autosave with partial data, so the historically
 * NOT NULL "always-filled" fields are relaxed here. Final validation that
 * these are present happens in SubmitApplicationRequest on submit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('authorized_representative_name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('cnic')->nullable()->change();
            $table->string('cell')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('authorized_representative_name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('cnic')->nullable(false)->change();
            $table->string('cell')->nullable(false)->change();
        });
    }
};
