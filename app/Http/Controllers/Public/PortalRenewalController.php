<?php

namespace App\Http\Controllers\Public;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\AutosaveRenewalRequest;
use App\Http\Requests\Portal\SubmitRenewalRequest;
use App\Models\Application;
use App\Models\Member;
use App\Services\Application\ApplicationPdfService;
use App\Services\Mail\TemplatedMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PortalRenewalController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        // Not a member yet → nothing to renew. Dashboard will route them to apply.
        if ($applicant->member_id === null) {
            return redirect()->route('portal.home');
        }

        $member = $applicant->member;
        $active = $applicant->activeApplication();

        // Already past Draft → status is in the dashboard, no edit allowed.
        if ($active !== null && $active->status !== ApplicationStatus::Draft) {
            return redirect()->route('portal.home');
        }

        // Renewals only make sense when the current membership has expired.
        // (If there's already a draft renewal we allow access so they can
        // finish editing it.)
        if ($active === null && ! $member->isExpired()) {
            return redirect()->route('portal.home');
        }

        return Inertia::render('PortalRenew', [
            'member' => $this->serialiseMember($member),
            'draft' => $active === null ? null : $this->serialiseDraft($active),
        ]);
    }

    public function autosave(AutosaveRenewalRequest $request): JsonResponse
    {
        $applicant = Auth::guard('applicant')->user();

        if ($applicant->member_id === null) {
            return response()->json(['status' => 'unauthorized'], 403);
        }

        // Expired-only guard (also lets an existing draft keep saving).
        $existing = $applicant->applications()
            ->where('status', ApplicationStatus::Draft)
            ->where('type', ApplicationType::Renewal)
            ->exists();
        if (! $existing && ! $applicant->member->isExpired()) {
            return response()->json(['status' => 'forbidden'], 403);
        }

        $data = $request->validated();
        $termsConfirmed = (bool) ($data['terms_confirmed'] ?? false);
        unset($data['terms_confirmed']);

        $application = $applicant->applications()
            ->where('status', ApplicationStatus::Draft)
            ->where('type', ApplicationType::Renewal)
            ->first();

        $payload = [
            ...$data,
            'type' => ApplicationType::Renewal,
            'status' => ApplicationStatus::Draft,
            'existing_membership_number' => $applicant->member->membership_number,
            'has_ntn' => filled($data['ntn_number'] ?? null),
            'terms_confirmed_at' => $termsConfirmed ? ($application?->terms_confirmed_at ?? now()) : null,
        ];

        if ($application === null) {
            if ($this->payloadIsEmpty($data) && ! $termsConfirmed) {
                return response()->json([
                    'status' => 'noop',
                    'application_id' => null,
                    'saved_at' => null,
                ]);
            }

            $application = $applicant->applications()->create($payload);
        } else {
            $application->fill($payload)->save();
        }

        return response()->json([
            'status' => 'saved',
            'application_id' => $application->id,
            'saved_at' => $application->updated_at->toIso8601String(),
        ]);
    }

    public function submit(SubmitRenewalRequest $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        if ($applicant->member_id === null) {
            return redirect()->route('portal.home');
        }

        $existing = $applicant->applications()
            ->where('status', ApplicationStatus::Draft)
            ->where('type', ApplicationType::Renewal)
            ->exists();
        if (! $existing && ! $applicant->member->isExpired()) {
            return redirect()->route('portal.home');
        }

        $data = $request->validated();
        unset($data['terms_confirmed'], $data['payment_proof']);

        $paymentProofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store(
                'payment-proofs/'.now()->format('Y/m'),
                'public',
            )
            : null;

        $application = $applicant->applications()
            ->where('status', ApplicationStatus::Draft)
            ->where('type', ApplicationType::Renewal)
            ->first();

        $payload = [
            ...$data,
            'type' => ApplicationType::Renewal,
            'status' => ApplicationStatus::Submitted,
            'existing_membership_number' => $applicant->member->membership_number,
            'has_ntn' => filled($data['ntn_number'] ?? null),
            'submitted_at' => now(),
            'terms_confirmed_at' => now(),
        ];

        if ($paymentProofPath !== null) {
            $payload['payment_proof_path'] = $paymentProofPath;
        }

        if ($application === null) {
            $application = $applicant->applications()->create($payload);
        } else {
            $application->fill($payload)->save();
        }

        $application->load('applicant');

        $this->notifySubmission($application);

        return redirect()->route('portal.home');
    }

    private function notifySubmission(Application $application): void
    {
        try {
            $pdf = app(ApplicationPdfService::class);
            $pdfBytes = $pdf->pdfBytesFor($application);

            app(TemplatedMailService::class)->send(
                key: 'application_submitted',
                to: $application->email,
                variables: [
                    'applicant_name' => $application->authorized_representative_name ?? '',
                    'company_name' => $application->company_name ?? '',
                    'portal_url' => url('/portal'),
                ],
                attachments: [[
                    'data' => $pdfBytes,
                    'filename' => $pdf->filename($application),
                    'mime' => 'application/pdf',
                ]],
            );
        } catch (Throwable $e) {
            Log::error('Failed to send renewal application_submitted email', [
                'application_id' => $application->id,
                'email' => $application->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function payloadIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if ($value === null || $value === '' || $value === false) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseMember(Member $member): array
    {
        return [
            'membership_number' => $member->membership_number,
            'membership_class' => $member->membership_class?->value,
            'authorized_representative_name' => $member->authorized_representative_name,
            'company_name' => $member->company_name,
            'email' => $member->email,
            'website' => $member->website,
            'established_year' => $member->established_year,
            'industry' => $member->industry?->value,
            'company_classification' => $member->company_classification?->value,
            'cnic' => $member->cnic,
            'cnic_expiry_date' => $member->cnic_expiry_date?->format('Y-m-d'),
            'turnover_pkr' => $member->turnover_pkr,
            'employees_count' => $member->employees_count,
            'ntn_number' => $member->ntn_number,
            'sales_tax_no' => $member->sales_tax_no,
            'address' => $member->address,
            'postal_code' => $member->postal_code,
            'district' => $member->district,
            'phone' => $member->phone,
            'cell' => $member->cell,
            'whatsapp' => $member->whatsapp,
            'alternate_no' => $member->alternate_no,
            'other_chamber_memberships' => $member->other_chamber_memberships,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialiseDraft(Application $application): array
    {
        return [
            'id' => $application->id,
            'membership_class' => $application->membership_class?->value,
            'industry' => $application->industry?->value,
            'authorized_representative_name' => $application->authorized_representative_name,
            'company_name' => $application->company_name,
            'email' => $application->email,
            'website' => $application->website,
            'established_year' => $application->established_year,
            'company_classification' => $application->company_classification?->value,
            'cnic' => $application->cnic,
            'cnic_expiry_date' => $application->cnic_expiry_date?->format('Y-m-d'),
            'turnover_pkr' => $application->turnover_pkr,
            'employees_count' => $application->employees_count,
            'ntn_number' => $application->ntn_number,
            'sales_tax_no' => $application->sales_tax_no,
            'address' => $application->address,
            'postal_code' => $application->postal_code,
            'district' => $application->district,
            'phone' => $application->phone,
            'cell' => $application->cell,
            'whatsapp' => $application->whatsapp,
            'alternate_no' => $application->alternate_no,
            'other_chamber_memberships' => $application->other_chamber_memberships,
            'terms_confirmed' => $application->terms_confirmed_at !== null,
            'updated_at' => $application->updated_at->toIso8601String(),
        ];
    }
}
