<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Mail\TemplatedMail;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Member;
use App\Services\Application\ApplicationWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApplicationWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private function newMemberApplication(array $overrides = []): Application
    {
        $applicant = Applicant::create([
            'email' => 'jane@example.test',
            'email_verified_at' => now(),
        ]);

        return Application::create(array_merge([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
            'authorized_representative_name' => 'Jane Doe',
            'company_name' => 'Jane Co.',
            'email' => $applicant->email,
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now(),
        ], $overrides));
    }

    public function test_mark_awaiting_documents_updates_status_and_sends_email(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication();

        app(ApplicationWorkflowService::class)->markAwaitingDocuments($app);

        $this->assertEquals(ApplicationStatus::AwaitingDocuments, $app->fresh()->status);
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $m) => $m->hasTo('jane@example.test'));
    }

    public function test_mark_awaiting_payment_updates_status_and_sends_email(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication();

        app(ApplicationWorkflowService::class)->markAwaitingPayment($app);

        $this->assertEquals(ApplicationStatus::AwaitingPayment, $app->fresh()->status);
        Mail::assertSentCount(1);
    }

    public function test_mark_ready_for_approval_does_not_send_email(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication();

        app(ApplicationWorkflowService::class)->markReadyForApproval($app);

        $this->assertEquals(ApplicationStatus::ReadyForApproval, $app->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_approve_with_payment_verified_finalises_immediately(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication(['payment_verified' => true]);
        $activeUntil = CarbonImmutable::now()->addYear();

        app(ApplicationWorkflowService::class)->approve($app, $activeUntil);

        $app->refresh();
        $this->assertEquals(ApplicationStatus::Approved, $app->status);
        $this->assertNotNull($app->applicant->fresh()->member_id);

        $member = $app->applicant->fresh()->member;
        $this->assertEquals($app->company_name, $member->company_name);
        $this->assertEquals($activeUntil->toDateString(), $member->active_until->toDateString());

        Mail::assertSent(TemplatedMail::class);
    }

    public function test_approve_without_payment_moves_to_awaiting_payment_and_records_intent(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication();
        $activeUntil = CarbonImmutable::now()->addYear();

        app(ApplicationWorkflowService::class)->approve($app, $activeUntil);

        $app->refresh();
        $this->assertEquals(ApplicationStatus::AwaitingPayment, $app->status);
        $this->assertNotNull($app->admin_approved_at);
        $this->assertEquals($activeUntil->toDateString(), $app->admin_approved_until->toDateString());
        // Member not yet created / activated.
        $this->assertNull($app->applicant->fresh()->member_id);
        // Awaiting-payment email went out, approval email did NOT.
        Mail::assertSentCount(1);
    }

    public function test_payment_verified_after_admin_pre_approval_auto_finalises(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication();
        $activeUntil = CarbonImmutable::now()->addYear();

        // Admin clicks Approve without payment → AwaitingPayment + awaiting_payment email.
        app(ApplicationWorkflowService::class)->approve($app, $activeUntil);
        Mail::assertSentCount(1);

        // Applicant uploads payment proof later (not covered here, that's a
        // controller test). Admin now verifies the payment.
        app(ApplicationWorkflowService::class)->recordPaymentDecision(
            $app->fresh(),
            verified: true,
            paymentDate: CarbonImmutable::now(),
            paymentMethod: 'bank_transfer',
            notes: 'Verified against bank statement.',
        );

        $app->refresh();
        $this->assertEquals(ApplicationStatus::Approved, $app->status);
        $this->assertNotNull($app->applicant->fresh()->member_id);
        $this->assertEquals($activeUntil->toDateString(), $app->applicant->fresh()->member->active_until->toDateString());

        // Both the awaiting-payment and the final approval email should have fired.
        Mail::assertSentCount(2);
    }

    public function test_approve_renewal_updates_existing_member_active_until(): void
    {
        Mail::fake();
        $member = Member::factory()->expired()->create();
        $applicant = Applicant::create([
            'email' => $member->email,
            'member_id' => $member->id,
            'email_verified_at' => now(),
        ]);
        $app = Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::Renewal,
            'status' => ApplicationStatus::ReadyForApproval,
            'existing_membership_number' => $member->membership_number,
            'authorized_representative_name' => $member->authorized_representative_name,
            'company_name' => $member->company_name,
            'email' => $member->email,
            'cnic' => $member->cnic,
            'cell' => $member->cell,
            'submitted_at' => now(),
            'payment_verified' => true,
        ]);
        $activeUntil = CarbonImmutable::now()->addYear();

        app(ApplicationWorkflowService::class)->approve($app, $activeUntil);

        $this->assertEquals($activeUntil->toDateString(), $member->fresh()->active_until->toDateString());
        $this->assertEquals(ApplicationStatus::Approved, $app->fresh()->status);
    }

    public function test_verify_payment_without_prior_admin_approval_moves_to_ready_for_approval(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication(['status' => ApplicationStatus::AwaitingPayment]);

        app(ApplicationWorkflowService::class)->recordPaymentDecision(
            $app,
            verified: true,
            paymentDate: CarbonImmutable::now(),
            paymentMethod: 'bank_transfer',
            notes: 'Deposit slip verified by Ayesha.',
        );

        $app->refresh();
        $this->assertTrue($app->payment_verified);
        $this->assertEquals(ApplicationStatus::ReadyForApproval, $app->status);
        Mail::assertNothingSent();
    }

    public function test_reject_payment_bounces_back_to_awaiting_payment_and_emails(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication(['status' => ApplicationStatus::ReadyForApproval]);

        app(ApplicationWorkflowService::class)->recordPaymentDecision(
            $app,
            verified: false,
            notes: 'Amount does not match membership fee.',
        );

        $app->refresh();
        $this->assertFalse($app->payment_verified);
        $this->assertEquals(ApplicationStatus::AwaitingPayment, $app->status);
        Mail::assertSent(TemplatedMail::class);
    }

    public function test_reject_stores_reason_and_sends_email(): void
    {
        Mail::fake();
        $app = $this->newMemberApplication();

        app(ApplicationWorkflowService::class)->reject($app, 'Incomplete documents.');

        $app->refresh();
        $this->assertEquals(ApplicationStatus::Rejected, $app->status);
        $this->assertEquals('Incomplete documents.', $app->rejection_reason);
        Mail::assertSent(TemplatedMail::class);
    }
}
