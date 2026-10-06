<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Enums\PaymentMethod;
use App\Services\Membership\MembershipIdGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_token',
        'applicant_id',
        'type',
        'status',
        'membership_class',
        'authorized_representative_name',
        'email',
        'cnic',
        'cnic_expiry_date',
        'turnover_pkr',
        'employees_count',
        'cell',
        'whatsapp',
        'alternate_no',
        'other_chamber_memberships',
        'phone',
        'company_name',
        'company_classification',
        'industry',
        'website',
        'established_year',
        'address',
        'district',
        'postal_code',
        'has_ntn',
        'ntn_number',
        'ntn_reason',
        'sales_tax_no',
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
        'terms_confirmed_at',
        'admin_approved_at',
        'admin_approved_until',
    ];

    protected $casts = [
        'type' => ApplicationType::class,
        'status' => ApplicationStatus::class,
        'membership_class' => MembershipClass::class,
        'company_classification' => CompanyClassification::class,
        'industry' => Industry::class,
        'payment_method' => PaymentMethod::class,
        'has_ntn' => 'boolean',
        'physical_form_received' => 'boolean',
        'documents_received' => 'boolean',
        'payment_verified' => 'boolean',
        'cnic_expiry_date' => 'date',
        'payment_date' => 'date',
        'submitted_at' => 'datetime',
        'terms_confirmed_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'admin_approved_until' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Application $application): void {
            if (empty($application->status_token)) {
                $application->status_token = Str::uuid();
            }

            // Membership ID is bound to the application at creation and never
            // changes afterwards. See MembershipIdGenerator for the format.
            if (empty($application->membership_id)) {
                $application->membership_id = app(MembershipIdGenerator::class)->generate();
            }
        });

        // Guard against anyone (admin, service code, Nova inline edit)
        // overwriting the ID once it has been assigned.
        static::updating(function (Application $application): void {
            if ($application->isDirty('membership_id') && $application->getOriginal('membership_id') !== null) {
                $application->membership_id = $application->getOriginal('membership_id');
            }
        });
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
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
