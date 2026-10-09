<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        'payment_instructions',
        'payment_submitted_at',
        'payment_processed_at',
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
        'payment_submitted_at' => 'datetime',
        'payment_processed_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Application $application): void {
            if (empty($application->status_token)) {
                $application->status_token = Str::uuid();
            }
        });

        // Guard against anyone (admin, service code, Nova inline edit)
        // overwriting the ID once it has been assigned.
        static::updating(function (Application $application): void {
            if ($application->isDirty('membership_id') && $application->getOriginal('membership_id') !== null) {
                $application->membership_id = $application->getOriginal('membership_id');
            }

            $informationFields = array_diff((new Member)->getFillable(), ['membership_number', 'active_until']);
            $informationChanged = $application->isDirty([...$informationFields, 'has_ntn', 'ntn_reason']);
            $proofChanged = $application->isDirty('payment_proof_path');
            $paymentDetailsChanged = $application->isDirty(['payment_date', 'payment_method']);
            $documentsRevoked = ($application->isDirty('physical_form_received') && ! $application->physical_form_received)
                || ($application->isDirty('documents_received') && ! $application->documents_received);

            $originalStatus = $application->getOriginal('status');
            if ($originalStatus->isTerminal() && ($informationChanged || $proofChanged || $paymentDetailsChanged || $documentsRevoked || $application->isDirty(['status', 'payment_verified']))) {
                throw ValidationException::withMessages(['application' => 'Completed applications cannot be changed.']);
            }

            if ($originalStatus !== ApplicationStatus::Draft && ! $originalStatus->isTerminal()) {
                if ($informationChanged) {
                    $application->admin_approved_at = null;
                    $application->admin_approved_until = null;
                    $application->payment_instructions = null;
                    $application->payment_submitted_at = null;
                    $application->physical_form_received = false;
                    $application->documents_received = false;
                    $application->payment_verified = false;
                    $application->payment_notes = null;
                    $application->status = ApplicationStatus::Submitted;
                } elseif ($documentsRevoked) {
                    $application->admin_approved_at = null;
                    $application->admin_approved_until = null;
                    $application->payment_instructions = null;
                    $application->payment_submitted_at = null;
                    $application->status = $application->payment_verified ? ApplicationStatus::ReadyForApproval : ApplicationStatus::Submitted;
                }

                if ($proofChanged || $paymentDetailsChanged) {
                    $application->payment_verified = false;
                    $application->payment_processed_at = null;
                    $application->payment_notes = null;
                    $application->status = $application->admin_approved_at !== null
                        ? (filled($application->payment_proof_path) && $application->payment_date !== null && $application->payment_method !== null
                            ? ApplicationStatus::ReadyForApproval : ApplicationStatus::AwaitingPayment)
                        : ($application->status === ApplicationStatus::AwaitingDocuments ? ApplicationStatus::AwaitingDocuments : ApplicationStatus::Submitted);
                }
            }

            if ($application->status === ApplicationStatus::Approved && (! $application->payment_verified
                || ! filled($application->payment_proof_path) || ! $application->physical_form_received
                || ! $application->documents_received || $application->admin_approved_at === null
                || $application->admin_approved_until === null || $application->payment_date === null
                || $application->payment_method === null)) {
                throw ValidationException::withMessages(['application' => 'Information, office documents, and an uploaded payment receipt must be approved before activation.']);
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

    public function canBeReviewed(): bool
    {
        return $this->status !== ApplicationStatus::Draft && ! $this->status->isTerminal();
    }

    public function canSubmitPayment(): bool
    {
        return $this->canBeReviewed() && $this->admin_approved_at !== null && filled($this->payment_instructions)
            && $this->physical_form_received && $this->documents_received;
    }
}
