<?php

namespace App\Http\Controllers\Public;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The dashboard shows exactly one state and one recommended action.
 *
 * Precedence (first match wins):
 *   1. application_in_progress, any draft/submitted/under-review app
 *   2. expired_member         , linked member whose active_until is past
 *   3. active_member          , linked member whose active_until is future
 *   4. new_applicant          , no linked member, no application
 */
class PortalDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Applicant $applicant */
        $applicant = Auth::guard('applicant')->user();
        $active = $applicant->activeApplication();
        $member = $applicant->member;

        [$state, $payload] = $this->resolveJourney($applicant, $active, $member);

        return Inertia::render('PortalDashboard', [
            'paymentMethods' => array_map(fn (PaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()], PaymentMethod::cases()),
            'applicant' => [
                'email' => $applicant->email,
                'name' => $applicant->name,
                'last_signed_in_at' => $applicant->last_signed_in_at?->toIso8601String(),
            ],
            'journey' => [
                'state' => $state,
                ...$payload,
            ],
        ]);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function resolveJourney(Applicant $applicant, ?Application $active, ?Member $member): array
    {
        if ($active !== null) {
            return ['application_in_progress', ['application' => $this->serialiseApplication($active)]];
        }

        if ($member !== null && $member->isExpired()) {
            return ['expired_member', ['member' => $this->serialiseMember($member)]];
        }

        if ($member !== null && $member->isActive()) {
            return ['active_member', ['member' => $this->serialiseMember($member, $applicant->applications()->where('status', 'approved')->latest('id')->first())]];
        }

        return ['new_applicant', []];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseApplication(Application $application): array
    {
        return [
            'id' => $application->id,
            'type' => $application->type->value,
            'status' => $application->status->value,
            'status_label' => $application->status->label(),
            'status_color' => $application->status->color(),
            'company_name' => $application->company_name,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'updated_at' => $application->updated_at->toIso8601String(),
            'has_payment_proof' => filled($application->payment_proof_path),
            'payment_date' => $application->payment_date?->format('Y-m-d'),
            'payment_method' => $application->payment_method?->value,
            'can_submit_payment' => $application->canSubmitPayment(),
            'payment_instructions' => $application->payment_instructions,
            'payment_submitted_at' => $application->payment_submitted_at?->toIso8601String(),
            'payment_verified' => $application->payment_verified,
            'information_approved' => $application->admin_approved_at !== null,
            'physical_form_received' => $application->physical_form_received,
            'documents_received' => $application->documents_received,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseMember(Member $member, ?Application $latestApproval = null): array
    {
        return [
            'membership_number' => $member->membership_number,
            'authorized_representative_name' => $member->authorized_representative_name,
            'company_name' => $member->company_name,
            'email' => $member->email,
            'membership_class' => $member->membership_class?->value,
            'active_until' => $member->active_until?->toIso8601String(),
            'payment_processed_at' => $latestApproval?->payment_processed_at?->toDateString(),
        ];
    }
}
