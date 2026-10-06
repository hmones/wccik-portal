<?php

namespace App\Http\Controllers\Public;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UploadPaymentProofRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Standalone payment proof upload. Used when the admin has moved the
 * application to Awaiting Payment and the applicant comes back to the
 * portal to attach their deposit slip. Replacement uploads are allowed —
 * the latest file wins.
 *
 * On a successful upload, if the application is sitting in Awaiting Payment
 * it auto-transitions to Ready for Approval so the admin sees it in their
 * approval queue on their next visit (we deliberately don't email admins —
 * they check the dashboard daily).
 */
class PaymentProofController extends Controller
{
    public function __invoke(UploadPaymentProofRequest $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        $application = $applicant->applications()->latest('id')->first();

        if ($application === null) {
            return redirect()->route('portal.home');
        }

        // Only applications in the middle of the admin workflow accept
        // an uploaded proof. If it's Approved/Rejected/Draft the applicant
        // shouldn't be uploading here.
        if (! in_array($application->status, [
            ApplicationStatus::Submitted,
            ApplicationStatus::AwaitingDocuments,
            ApplicationStatus::AwaitingPayment,
            ApplicationStatus::ReadyForApproval,
        ], strict: true)) {
            return redirect()->route('portal.home');
        }

        $path = $request->file('payment_proof')->store(
            'payment-proofs/'.now()->format('Y/m'),
            'public',
        );

        $updates = ['payment_proof_path' => $path];

        if ($application->status === ApplicationStatus::AwaitingPayment) {
            $updates['status'] = ApplicationStatus::ReadyForApproval;
        }

        $application->update($updates);

        return redirect()->route('portal.home')->with('status', 'payment_proof_uploaded');
    }
}
