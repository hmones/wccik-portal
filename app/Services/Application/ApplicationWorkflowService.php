<?php

namespace App\Services\Application;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\PaymentMethod;
use App\Jobs\SendTemplatedMail;
use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Member;
use App\Models\User;
use App\Services\Membership\MembershipIdGenerator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Notifications\NovaNotification;
use Laravel\Nova\URL;

class ApplicationWorkflowService
{
    public function acceptForm(Application $application, string $paymentInstructions): void
    {
        DB::transaction(function () use ($application, $paymentInstructions): void {
            $current = $this->lockedApplication($application);
            $this->assertReviewable($current);
            if (! $current->physical_form_received || ! $current->documents_received) {
                throw ValidationException::withMessages([
                    'application' => 'Receive the signed form and all supporting documents before accepting the form.',
                ]);
            }
            if (trim($paymentInstructions) === '') {
                throw ValidationException::withMessages(['payment_instructions' => 'Supply the approved fee and bank/payment instructions.']);
            }
            if ($current->admin_approved_at !== null && $current->payment_instructions === trim($paymentInstructions)) {
                return;
            }

            $current->update([
                'admin_approved_at' => now(),
                'payment_instructions' => trim($paymentInstructions),
                'payment_verified' => false,
                'status' => $this->hasCompletePayment($current)
                    ? ApplicationStatus::ReadyForApproval : ApplicationStatus::AwaitingPayment,
            ]);
            $this->notifyAfterCommit($current, 'application_form_accepted');
            if ($this->hasCompletePayment($current)) {
                $this->notifyAdminsOfPayment($current);
            }
        });
        $application->refresh();
    }

    public function recordPaymentSubmission(Application $application, string $path, string $date, string $method): void
    {
        DB::transaction(function () use ($application, $path, $date, $method): void {
            $current = $this->lockedApplication($application);
            $this->assertPaymentStage($current);
            $details = Validator::make(['payment_date' => $date, 'payment_method' => $method], [
                'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            ])->validate();
            $current->update([
                ...$details,
                'payment_proof_path' => $path,
                'payment_verified' => false,
                'payment_submitted_at' => now(),
                'status' => ApplicationStatus::ReadyForApproval,
            ]);
            $this->notifyAdminsOfPayment($current);
        });
        $application->refresh();
    }

    private function hasCompletePayment(Application $application): bool
    {
        return filled($application->payment_proof_path) && $application->payment_date !== null && $application->payment_method !== null;
    }

    public function recordPaymentDecision(
        Application $application,
        bool $verified,
        ?CarbonInterface $paymentDate = null,
        ?string $paymentMethod = null,
        ?string $notes = null,
        ?CarbonInterface $activeUntil = null,
        ?CarbonInterface $processedAt = null,
    ): void {
        DB::transaction(function () use ($application, $verified, $paymentDate, $paymentMethod, $notes, $activeUntil, $processedAt): void {
            $current = $this->lockedApplication($application);
            if ($current->isApproved() && $verified) {
                return;
            }
            $this->assertPaymentStage($current);
            if ($verified && ! filled($current->payment_proof_path)) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Upload the payment receipt before verifying payment, including receipts received at the office.',
                ]);
            }

            $details = Validator::make([
                'payment_date' => ($paymentDate ?? $current->payment_date)?->toDateString(),
                'payment_method' => $paymentMethod ?? $current->payment_method?->value,
            ], [
                'payment_date' => [$verified ? 'required' : 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
                'payment_method' => [$verified ? 'required' : 'nullable', Rule::enum(PaymentMethod::class)],
            ])->validate();

            if ($verified) {
                $expiry = $activeUntil ?? $current->admin_approved_until;
                if ($expiry === null) {
                    throw ValidationException::withMessages(['active_until' => 'Choose the membership expiry date.']);
                }
                $this->assertFutureExpiry($expiry);
                $processed = Validator::make(['payment_date' => $details['payment_date'], 'processed_at' => ($processedAt ?? today())->toDateString()], [
                    'processed_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:payment_date'],
                ])->validate();
            }

            // Amend the supplied details first; changing them invalidates old verification.
            $current->update($details);
            $current->update([
                'payment_verified' => $verified,
                'payment_notes' => $notes,
                'status' => $verified ? ApplicationStatus::ReadyForApproval : ApplicationStatus::AwaitingPayment,
            ]);

            if ($verified) {
                $current->update(['admin_approved_until' => $expiry, 'payment_processed_at' => $processed['processed_at']]);
                $this->finaliseIfComplete($current);
            } else {
                $this->notifyAfterCommit($current, 'application_payment_correction', ['payment_notes' => $notes ?? 'Please supply valid payment proof.']);
            }
        });
        $application->refresh();
    }

    public function markAwaitingPayment(Application $application): void
    {
        DB::transaction(function () use ($application): void {
            $current = $this->lockedApplication($application);
            $this->assertPaymentStage($current);
            if ($current->payment_verified) {
                throw ValidationException::withMessages(['payment' => 'Payment is already verified.']);
            }
            $current->update(['status' => $this->hasCompletePayment($current) ? ApplicationStatus::ReadyForApproval : ApplicationStatus::AwaitingPayment]);
            $this->notifyAfterCommit($current, 'application_form_accepted');
        });
        $application->refresh();
    }

    public function reject(Application $application, string $reason): void
    {
        DB::transaction(function () use ($application, $reason): void {
            $current = $this->lockedApplication($application);
            if ($current->isRejected()) {
                return;
            }
            $this->assertReviewable($current);
            $current->update([
                'status' => ApplicationStatus::Rejected,
                'rejection_reason' => $reason,
                'admin_approved_at' => null,
                'admin_approved_until' => null,
            ]);
            $this->notifyAfterCommit($current, 'application_rejected', ['rejection_reason' => $reason]);
        });
        $application->refresh();
    }

    private function lockedApplication(Application $application): Application
    {
        return Application::query()->lockForUpdate()->findOrFail($application->id);
    }

    private function assertReviewable(Application $application): void
    {
        if (! $application->canBeReviewed()) {
            throw ValidationException::withMessages([
                'application' => 'Only submitted, pending applications can be reviewed.',
            ]);
        }
    }

    private function assertPaymentStage(Application $application): void
    {
        if (! $application->canSubmitPayment()) {
            throw ValidationException::withMessages(['payment' => 'Payment is available only after the office accepts the form and documents.']);
        }
    }

    private function assertFutureExpiry(CarbonInterface $activeUntil): void
    {
        if ($activeUntil->toDateString() <= today()->toDateString()) {
            throw ValidationException::withMessages([
                'active_until' => 'Choose a future membership expiry date before completing approval.',
            ]);
        }
    }

    // Called inside the locked transaction so payment processing activates once.
    private function finaliseIfComplete(Application $application): void
    {
        if (! $application->physical_form_received || ! $application->documents_received
            || $application->admin_approved_at === null || $application->admin_approved_until === null
            || ! $application->payment_verified || ! filled($application->payment_proof_path)
            || $application->payment_date === null || $application->payment_method === null) {
            return;
        }

        $this->assertFutureExpiry($application->admin_approved_until);
        $member = $this->resolveMemberForApplication($application);
        $member->fill($this->memberInformation($application));
        $member->active_until = $application->admin_approved_until;
        $member->save();

        $application->update(['status' => ApplicationStatus::Approved]);
        if ($application->applicant && $application->applicant->member_id === null) {
            $application->applicant->update(['member_id' => $member->id]);
        }

        $variables = [
            'membership_id' => $member->membership_number,
            'active_until' => $application->admin_approved_until->toFormattedDateString(),
            'payment_processed_at' => $application->payment_processed_at->toFormattedDateString(),
        ];
        $this->notifyAfterCommit($application, 'application_approved', $variables);
        $this->notifyAfterCommit($application, 'membership_certificate_collection', $variables);
    }

    public function notifyAdminsOfPayment(Application $application): void
    {
        $adminUrl = url('/'.trim((string) config('nova.path'), '/').'/resources/applications/'.$application->id);
        foreach (User::all() as $admin) {
            if (! Gate::forUser($admin)->allows('viewNova')) {
                continue;
            }
            $admin->notify(NovaNotification::make()
                ->message('Payment submitted for '.($application->company_name ?? 'application #'.$application->id).'. Review the receipt and create membership.')
                ->action('Review payment', URL::remote($adminUrl))
                ->icon('currency-dollar')->type('info'));
            $variables = [
                'applicant_name' => $application->authorized_representative_name ?? '',
                'company_name' => $application->company_name ?? '',
                'cnic' => $application->cnic ?? '',
                'payment_date' => $application->payment_date?->toDateString(),
                'payment_method' => $application->payment_method?->label(),
                'admin_url' => $adminUrl,
            ];
            SendTemplatedMail::dispatch('admin_application_payment_submitted', $admin->email, $variables, 'en')->afterCommit();
        }
    }

    /** @param array<string, scalar|null> $variables */
    private function notifyAfterCommit(Application $application, string $key, array $variables = []): void
    {
        if ($application->email) {
            if (! EmailTemplate::where('key', $key)->exists()) {
                throw ValidationException::withMessages(['email' => 'Email template '.$key.' is missing. Apply the workflow migration before continuing.']);
            }
            SendTemplatedMail::dispatch($key, $application->email, [
                'applicant_name' => $application->authorized_representative_name ?? '',
                'company_name' => $application->company_name ?? '',
                'portal_url' => url('/portal'),
                'payment_instructions' => $application->payment_instructions ?? '',
                ...$variables,
            ], app()->getLocale())->afterCommit();
        }
    }

    /** @return array<string, mixed> */
    private function memberInformation(Application $application): array
    {
        $fields = array_diff((new Member)->getFillable(), ['membership_number', 'active_until']);

        return $application->only($fields);
    }

    private function resolveMemberForApplication(Application $application): Member
    {
        if ($application->applicant?->member) {
            return $application->applicant->member;
        }
        if ($application->type === ApplicationType::Renewal) {
            $member = Member::where('membership_number', $application->existing_membership_number)->first();
            if ($member === null) {
                throw ValidationException::withMessages(['application' => 'The membership being renewed could not be found.']);
            }

            return $member;
        }

        if (! filled($application->membership_id)) {
            $application->update(['membership_id' => app(MembershipIdGenerator::class)->generate()]);
        }

        return new Member(['membership_number' => $application->membership_id]);
    }
}
