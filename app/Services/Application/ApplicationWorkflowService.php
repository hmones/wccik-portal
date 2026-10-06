<?php

namespace App\Services\Application;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\Member;
use App\Services\Mail\TemplatedMailService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Admin workflow transitions. All transitions are atomic (DB transaction)
 * and emit a templated email keyed to the resulting status.
 */
class ApplicationWorkflowService
{
    public function __construct(private readonly TemplatedMailService $mailer) {}

    public function markAwaitingDocuments(Application $application): void
    {
        $this->transition(
            $application,
            ApplicationStatus::AwaitingDocuments,
            'application_awaiting_documents',
        );
    }

    public function markAwaitingPayment(Application $application): void
    {
        $this->transition(
            $application,
            ApplicationStatus::AwaitingPayment,
            'application_awaiting_payment',
        );
    }

    public function markReadyForApproval(Application $application): void
    {
        // Internal-only transition; no applicant-facing email.
        DB::transaction(function () use ($application): void {
            $application->update(['status' => ApplicationStatus::ReadyForApproval]);
        });
    }

    /**
     * Admin's "Approve" action.
     *
     * Branches on payment state:
     *   payment_verified = true   → full approval right now; member activated,
     *                               applicant gets the approval email.
     *   payment_verified = false  → "docs approved, pay now" state. We record
     *                               the admin's approval intent (admin_approved_at
     *                               + admin_approved_until) and move the
     *                               application to AwaitingPayment with the
     *                               awaiting-payment email. The member record
     *                               is NOT created or activated yet — that
     *                               happens later in recordPaymentDecision()
     *                               once payment is verified.
     */
    public function approve(
        Application $application,
        CarbonInterface $activeUntil,
    ): void {
        if ($application->payment_verified) {
            $this->finaliseApproval($application, $activeUntil);

            return;
        }

        // Docs approved, awaiting payment. Record the admin's intent so that
        // when payment is eventually verified we can auto-finalise without
        // asking the admin to pick the expiry date all over again.
        DB::transaction(function () use ($application, $activeUntil): void {
            $application->update([
                'status' => ApplicationStatus::AwaitingPayment,
                'admin_approved_at' => now(),
                'admin_approved_until' => $activeUntil,
            ]);
        });

        if ($application->email) {
            $this->mailer->send('application_awaiting_payment', $application->email, [
                'applicant_name' => $application->authorized_representative_name ?? '',
                'company_name' => $application->company_name ?? '',
                'portal_url' => url('/portal'),
            ]);
        }
    }

    /**
     * Record a payment decision from an admin.
     *
     *   verified = true  → mark verified; if the admin had already approved
     *                      the docs (admin_approved_at is set), finalise the
     *                      approval right now using admin_approved_until —
     *                      member is activated, approval email fires.
     *                      Otherwise just advance from AwaitingPayment to
     *                      ReadyForApproval so admin sees it in the queue.
     *   verified = false → flip the flag off, bounce back to AwaitingPayment,
     *                      send the awaiting-payment email.
     */
    public function recordPaymentDecision(
        Application $application,
        bool $verified,
        ?CarbonInterface $paymentDate = null,
        ?string $paymentMethod = null,
        ?string $notes = null,
    ): void {
        $shouldAutoFinalise = $verified
            && $application->admin_approved_at !== null
            && $application->admin_approved_until !== null;

        DB::transaction(function () use ($application, $verified, $paymentDate, $paymentMethod, $notes, $shouldAutoFinalise): void {
            $updates = [
                'payment_verified' => $verified,
                'payment_date' => $paymentDate,
                'payment_notes' => $notes,
            ];

            if ($paymentMethod !== null) {
                $updates['payment_method'] = $paymentMethod;
            }

            // Status transitions, in precedence order:
            //  - rejecting payment → always back to AwaitingPayment
            //  - verifying & no prior admin approval → ReadyForApproval
            //  - verifying & prior approval → finalised below after update
            if (! $verified && $application->status !== ApplicationStatus::Rejected) {
                $updates['status'] = ApplicationStatus::AwaitingPayment;
            } elseif ($verified && ! $shouldAutoFinalise) {
                $updates['status'] = ApplicationStatus::ReadyForApproval;
            }

            $application->update($updates);
        });

        if ($shouldAutoFinalise) {
            // Reload to pick up the fresh verified state before finalising.
            $this->finaliseApproval(
                $application->refresh(),
                $application->admin_approved_until,
            );

            return;
        }

        if (! $verified && $application->email) {
            $this->mailer->send('application_awaiting_payment', $application->email, [
                'applicant_name' => $application->authorized_representative_name ?? '',
                'company_name' => $application->company_name ?? '',
                'portal_url' => url('/portal'),
            ]);
        }
    }

    /**
     * Internal: perform the full approval. Creates/links the member record,
     * activates it, status → Approved, approval email fires.
     */
    private function finaliseApproval(Application $application, CarbonInterface $activeUntil): void
    {
        DB::transaction(function () use ($application, $activeUntil): void {
            $member = $this->resolveMemberForApplication($application);
            $member->active_until = $activeUntil;
            $member->save();

            // membership_id was already assigned at application creation and
            // is write-protected by the model. On approval we just transition
            // the status + stamp the admin-approval intent if it wasn't set
            // (happens when payment was verified before admin clicked Approve).
            $updates = ['status' => ApplicationStatus::Approved];
            if ($application->admin_approved_at === null) {
                $updates['admin_approved_at'] = now();
                $updates['admin_approved_until'] = $activeUntil;
            }
            $application->update($updates);

            // For brand-new members, link the applicant so their next sign-in
            // lands on the active_member dashboard state.
            if ($application->applicant && $application->applicant->member_id === null) {
                $application->applicant->update(['member_id' => $member->id]);
            }
        });

        if ($application->email) {
            $this->mailer->send('application_approved', $application->email, [
                'applicant_name' => $application->authorized_representative_name ?? '',
                'company_name' => $application->company_name ?? '',
                'membership_id' => $application->membership_id ?? '',
                'active_until' => $activeUntil->toFormattedDateString(),
                'portal_url' => url('/portal'),
            ]);
        }
    }

    public function reject(Application $application, string $reason): void
    {
        DB::transaction(function () use ($application, $reason): void {
            $application->update([
                'status' => ApplicationStatus::Rejected,
                'rejection_reason' => $reason,
            ]);
        });

        if ($application->email) {
            $this->mailer->send('application_rejected', $application->email, [
                'applicant_name' => $application->authorized_representative_name ?? '',
                'company_name' => $application->company_name ?? '',
                'rejection_reason' => $reason,
            ]);
        }
    }

    private function transition(
        Application $application,
        ApplicationStatus $status,
        string $templateKey,
    ): void {
        DB::transaction(function () use ($application, $status): void {
            $application->update(['status' => $status]);
        });

        if ($application->email) {
            $this->mailer->send($templateKey, $application->email, [
                'applicant_name' => $application->authorized_representative_name ?? '',
                'company_name' => $application->company_name ?? '',
                'portal_url' => url('/portal'),
            ]);
        }
    }

    /**
     * For a renewal, return the existing linked member. For a new-member
     * approval, create a Member row from the application data and link it.
     */
    private function resolveMemberForApplication(Application $application): Member
    {
        if ($application->applicant?->member) {
            return $application->applicant->member;
        }

        if ($application->type === ApplicationType::Renewal && $application->existing_membership_number) {
            $member = Member::where('membership_number', $application->existing_membership_number)->first();
            if ($member !== null) {
                return $member;
            }
        }

        // Create a fresh member record from the application snapshot. The new
        // member inherits the membership_id that was assigned to the
        // application at creation — that is the canonical WCCIK ID.
        $member = Member::create([
            'membership_number' => $application->membership_id,
            'membership_class' => $application->membership_class?->value,
            'authorized_representative_name' => $application->authorized_representative_name ?? '',
            'company_name' => $application->company_name ?? '',
            'email' => $application->email ?? '',
            'website' => $application->website,
            'established_year' => $application->established_year,
            'industry' => $application->industry?->value,
            'company_classification' => $application->company_classification?->value,
            'cnic' => $application->cnic ?? '',
            'cnic_expiry_date' => $application->cnic_expiry_date,
            'turnover_pkr' => $application->turnover_pkr,
            'employees_count' => $application->employees_count,
            'ntn_number' => $application->ntn_number,
            'sales_tax_no' => $application->sales_tax_no,
            'address' => $application->address,
            'postal_code' => $application->postal_code,
            'district' => $application->district,
            'phone' => $application->phone,
            'cell' => $application->cell ?? '',
            'whatsapp' => $application->whatsapp,
            'alternate_no' => $application->alternate_no,
            'other_chamber_memberships' => $application->other_chamber_memberships,
        ]);

        return $member;
    }
}
