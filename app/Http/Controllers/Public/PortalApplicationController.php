<?php

namespace App\Http\Controllers\Public;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\AutosaveApplicationRequest;
use App\Http\Requests\Portal\SubmitApplicationRequest;
use App\Models\Application;
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

class PortalApplicationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        // Already a WCCIK member — the dashboard shows them their membership
        // record (or routes to renew). New-member application does not apply.
        if ($applicant->member_id !== null) {
            return redirect()->route('portal.home');
        }

        $active = $applicant->activeApplication();

        // If the active application is already past Draft, send them to the
        // dashboard — they can't edit a submitted application.
        if ($active !== null && $active->status !== ApplicationStatus::Draft) {
            return redirect()->route('portal.home');
        }

        return Inertia::render('PortalApply', [
            'draft' => $active === null ? null : $this->serialiseDraft($active),
            'applicantEmail' => $applicant->email,
        ]);
    }

    public function autosave(AutosaveApplicationRequest $request): JsonResponse
    {
        $applicant = Auth::guard('applicant')->user();
        $data = $request->validated();

        $termsConfirmed = (bool) ($data['terms_confirmed'] ?? false);
        unset($data['terms_confirmed']);

        $hasNtn = array_key_exists('has_ntn', $data) ? (bool) $data['has_ntn'] : null;
        if ($hasNtn === false) {
            $data['ntn_number'] = null;
        }

        $application = $applicant->applications()
            ->where('status', ApplicationStatus::Draft)
            ->first();

        $payload = [
            ...$data,
            'email' => $applicant->email,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Draft,
            'terms_confirmed_at' => $termsConfirmed ? ($application?->terms_confirmed_at ?? now()) : null,
        ];

        if ($application === null) {
            // Materialise the draft row on first save, only if there is
            // *something* worth saving — avoids empty-row spam from a blur
            // on an untouched field.
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

    public function submit(SubmitApplicationRequest $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        $application = $applicant->applications()
            ->where('status', ApplicationStatus::Draft)
            ->first();

        $data = $request->validated();
        $data['has_ntn'] = (bool) $data['has_ntn'];
        if (! $data['has_ntn']) {
            $data['ntn_number'] = null;
        }
        unset($data['terms_confirmed'], $data['payment_proof']);

        $paymentProofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store(
                'payment-proofs/'.now()->format('Y/m'),
                'public',
            )
            : null;

        $payload = [
            ...$data,
            'email' => $applicant->email,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
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
        // The submission is already persisted — if sending the confirmation
        // fails (SMTP down, template missing, PDF error), log and continue
        // instead of 500ing on the user. The applicant still sees their
        // Submitted status in the portal.
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
            Log::error('Failed to send application_submitted email', [
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
    private function serialiseDraft(Application $application): array
    {
        return [
            'id' => $application->id,
            'membership_class' => $application->membership_class?->value,
            'industry' => $application->industry?->value,
            'authorized_representative_name' => $application->authorized_representative_name,
            'cnic' => $application->cnic,
            'cnic_expiry_date' => $application->cnic_expiry_date?->format('Y-m-d'),
            'company_name' => $application->company_name,
            'website' => $application->website,
            'established_year' => $application->established_year,
            'company_classification' => $application->company_classification?->value,
            'turnover_pkr' => $application->turnover_pkr,
            'employees_count' => $application->employees_count,
            'address' => $application->address,
            'postal_code' => $application->postal_code,
            'district' => $application->district,
            'phone' => $application->phone,
            'cell' => $application->cell,
            'whatsapp' => $application->whatsapp,
            'alternate_no' => $application->alternate_no,
            'has_ntn' => (bool) $application->has_ntn,
            'ntn_number' => $application->ntn_number,
            'sales_tax_no' => $application->sales_tax_no,
            'other_chamber_memberships' => $application->other_chamber_memberships,
            'terms_confirmed' => $application->terms_confirmed_at !== null,
            'updated_at' => $application->updated_at->toIso8601String(),
        ];
    }
}
