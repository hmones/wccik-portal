<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_token',
        'type',
        'status',
        'authorized_representative_name',
        'email',
        'cnic',
        'cell',
        'whatsapp',
        'phone',
        'company_name',
        'company_classification',
        'address',
        'district',
        'has_ntn',
        'ntn_number',
        'ntn_reason',
        'existing_membership_number',
        'payment_proof_path',
        'physical_form_received',
        'documents_received',
        'rejection_reason',
        'membership_id',
        'payment_method',
        'payment_verified',
        'payment_date',
        'payment_notes',
        'submitted_at',
    ];

    protected $casts = [
        'type' => ApplicationType::class,
        'status' => ApplicationStatus::class,
        'company_classification' => CompanyClassification::class,
        'payment_method' => PaymentMethod::class,
        'has_ntn' => 'boolean',
        'physical_form_received' => 'boolean',
        'documents_received' => 'boolean',
        'payment_verified' => 'boolean',
        'payment_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Application $application): void {
            if (empty($application->status_token)) {
                $application->status_token = Str::uuid();
            }
        });
    }

    public function isNewMember(): bool
    {
        return $this->type === ApplicationType::NewMember;
    }

    public function isRenewal(): bool
    {
        return $this->type === ApplicationType::Renewal;
    }

    public function isApproved(): bool
    {
        return $this->status === ApplicationStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ApplicationStatus::Rejected;
    }
}
