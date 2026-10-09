<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UploadPaymentProofRequest;
use App\Services\Application\ApplicationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentProofController extends Controller
{
    public function __invoke(UploadPaymentProofRequest $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        DB::transaction(function () use ($applicant, $request): void {
            $application = $applicant->applications()->latest('id')->lockForUpdate()->first();
            abort_unless($application !== null && $application->canSubmitPayment(), 403);

            $path = $request->file('payment_proof')->store(
                'payment-proofs/'.now()->format('Y/m'),
                'public',
            );

            app(ApplicationWorkflowService::class)->recordPaymentSubmission(
                $application, $path, $request->validated('payment_date'), $request->validated('payment_method'),
            );
        });

        return redirect()->route('portal.home')->with('status', 'payment_proof_uploaded');
    }
}
