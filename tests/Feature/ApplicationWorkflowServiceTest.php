<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Mail\TemplatedMail;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Member;
use App\Models\User;
use App\Services\Application\ApplicationWorkflowService;
use Carbon\CarbonImmutable;
use Database\Seeders\MembershipWorkflowEmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApplicationWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private function application(array $overrides = []): Application
    {
        Mail::fake();
        $applicant = Applicant::create(['email' => 'jane'.Applicant::count().'@example.test', 'email_verified_at' => now()]);

        return Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
            'authorized_representative_name' => 'Jane Doe',
            'company_name' => 'Jane Co.',
            'email' => $applicant->email,
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now(),
            ...$overrides,
        ]);
    }

    private function acceptedApplication(array $overrides = []): Application
    {
        return $this->application([
            'admin_approved_at' => now(),
            'physical_form_received' => true,
            'documents_received' => true,
            'payment_instructions' => 'Test fee and test bank instructions',
            'status' => ApplicationStatus::AwaitingPayment,
            ...$overrides,
        ]);
    }

    private function paymentApplication(array $overrides = []): Application
    {
        return $this->acceptedApplication([
            'payment_proof_path' => 'payment-proofs/receipt.pdf',
            'payment_date' => today()->subDays(2),
            'payment_method' => 'bank_transfer',
            'status' => ApplicationStatus::ReadyForApproval,
            ...$overrides,
        ]);
    }

    public function test_accepting_form_sends_payment_instructions_without_creating_membership(): void
    {
        $application = $this->application(['physical_form_received' => true, 'documents_received' => true]);
        $service = app(ApplicationWorkflowService::class);
        $service->acceptForm($application, 'Approved fee and bank instructions');
        $this->assertTrue($application->canSubmitPayment());
        $this->assertEquals(ApplicationStatus::AwaitingPayment, $application->status);
        $this->assertNull($application->membership_id);
        $this->assertDatabaseCount('members', 0);
        Mail::assertSent(TemplatedMail::class, fn ($mail) => str_contains($mail->resolvedBody, 'Approved fee and bank instructions')
            && str_contains($mail->resolvedBody, 'within 7 days after payment is processed'));
        $service->acceptForm($application, 'Approved fee and bank instructions');
        Mail::assertSentCount(1);
    }

    public function test_form_acceptance_requires_office_documents(): void
    {
        foreach (['physical_form_received', 'documents_received'] as $field) {
            $application = $this->application(['physical_form_received' => true, 'documents_received' => true, $field => false]);
            try {
                app(ApplicationWorkflowService::class)->acceptForm($application, 'Payment instructions');
                $this->fail('Office documents are required.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('application', $e->errors());
            }
            $this->assertNull($application->fresh()->admin_approved_at);
        }
        Mail::assertNothingSent();
    }

    public function test_form_acceptance_requires_real_payment_instructions(): void
    {
        $application = $this->application(['physical_form_received' => true, 'documents_received' => true]);
        $this->expectException(ValidationException::class);
        app(ApplicationWorkflowService::class)->acceptForm($application, ' ');
    }

    public function test_missing_acceptance_template_rolls_back_acceptance(): void
    {
        $application = $this->application(['physical_form_received' => true, 'documents_received' => true]);
        EmailTemplate::where('key', 'application_form_accepted')->delete();
        try {
            app(ApplicationWorkflowService::class)->acceptForm($application, 'Payment instructions');
            $this->fail('Missing template must not silently accept a form.');
        } catch (ValidationException) {
            $this->assertNull($application->fresh()->admin_approved_at);
        }
    }

    public function test_workflow_templates_are_restored_without_overwriting_admin_copy(): void
    {
        $template = EmailTemplate::where('key', 'application_form_accepted')->firstOrFail();
        $template->update(['body_en' => 'Client-approved payment instructions']);
        EmailTemplate::where('key', 'admin_application_payment_submitted')->delete();

        $this->seed(MembershipWorkflowEmailTemplateSeeder::class);

        $this->assertEquals('Client-approved payment instructions', $template->fresh()->body_en);
        $this->assertDatabaseHas('email_templates', ['key' => 'admin_application_payment_submitted']);
    }

    public function test_payment_cannot_be_verified_before_form_acceptance(): void
    {
        $application = $this->application(['payment_proof_path' => 'old.pdf', 'payment_date' => today(), 'payment_method' => 'cheque']);
        $this->expectException(ValidationException::class);
        app(ApplicationWorkflowService::class)->recordPaymentDecision($application, true, activeUntil: CarbonImmutable::now()->addYear());
    }

    public function test_payment_cannot_be_requested_before_form_acceptance(): void
    {
        $application = $this->application();
        $this->expectException(ValidationException::class);
        app(ApplicationWorkflowService::class)->markAwaitingPayment($application);
    }

    public function test_payment_submission_alerts_every_authorised_admin_only(): void
    {
        $application = $this->acceptedApplication();
        $first = User::factory()->create(['email' => 'admin1@example.test']);
        $second = User::factory()->create(['email' => 'admin2@example.test']);
        User::factory()->create(['email' => 'outsider@example.test']);
        Gate::define('viewNova', fn (User $user) => $user->email !== 'outsider@example.test');
        app(ApplicationWorkflowService::class)->recordPaymentSubmission($application, 'receipt.pdf', today()->toDateString(), 'cheque');
        $this->assertEquals(ApplicationStatus::ReadyForApproval, $application->status);
        $this->assertNotNull($application->payment_submitted_at);
        $this->assertFalse($application->payment_verified);
        $this->assertDatabaseCount('members', 0);
        $this->assertDatabaseCount('nova_notifications', 2);
        foreach ([$first, $second] as $admin) {
            Mail::assertSent(TemplatedMail::class, fn ($mail) => $mail->hasTo($admin->email)
                && str_contains($mail->resolvedBody, 'Payment is ready for review'));
        }
        Mail::assertNotSent(TemplatedMail::class, fn ($mail) => $mail->hasTo('outsider@example.test'));
    }

    public function test_processing_payment_creates_membership_and_sends_two_final_emails_once(): void
    {
        $application = $this->paymentApplication();
        $stale = $application->fresh();
        $expiry = CarbonImmutable::now()->addYear();
        $service = app(ApplicationWorkflowService::class);
        $service->recordPaymentDecision($application, true, activeUntil: $expiry, processedAt: CarbonImmutable::today());
        $this->assertEquals(ApplicationStatus::Approved, $application->status);
        $this->assertNotNull($application->membership_id);
        $member = $application->applicant->fresh()->member;
        $this->assertEquals($application->membership_id, $member->membership_number);
        $this->assertEquals($expiry->toDateString(), $member->active_until->toDateString());
        $this->assertEquals(today()->toDateString(), $application->payment_processed_at->toDateString());
        Mail::assertSentCount(2);
        Mail::assertSent(TemplatedMail::class, fn ($mail) => str_contains($mail->resolvedBody, 'within 7 days after payment processing'));
        $service->recordPaymentDecision($stale, true, activeUntil: $expiry);
        $this->assertDatabaseCount('members', 1);
        Mail::assertSentCount(2);
    }

    public function test_processing_renewal_updates_member_and_preserves_membership_number(): void
    {
        $member = Member::factory()->expired()->create();
        $application = $this->paymentApplication([
            'type' => ApplicationType::Renewal,
            'existing_membership_number' => $member->membership_number,
            'company_name' => 'Reviewed renewal company',
        ]);
        $application->applicant->update(['member_id' => $member->id]);
        app(ApplicationWorkflowService::class)->recordPaymentDecision($application, true, activeUntil: CarbonImmutable::now()->addYear());
        $this->assertEquals('Reviewed renewal company', $member->fresh()->company_name);
        $this->assertEquals(ApplicationStatus::Approved, $application->status);
        $this->assertDatabaseCount('members', 1);
        Mail::assertSent(TemplatedMail::class, fn ($mail) => str_contains($mail->resolvedBody, $member->membership_number));
    }

    public function test_processing_payment_requires_receipt_date_method_and_future_expiry(): void
    {
        foreach (['payment_proof_path', 'payment_date', 'payment_method'] as $field) {
            $application = $this->paymentApplication([$field => null]);
            try {
                app(ApplicationWorkflowService::class)->recordPaymentDecision($application, true, activeUntil: CarbonImmutable::now()->addYear());
                $this->fail('Complete payment is required.');
            } catch (ValidationException) {
                $this->assertFalse($application->fresh()->payment_verified);
            }
        }
        $application = $this->paymentApplication();
        $this->expectException(ValidationException::class);
        app(ApplicationWorkflowService::class)->recordPaymentDecision($application, true, activeUntil: CarbonImmutable::yesterday());
    }

    public function test_rejected_payment_requests_correction_without_resetting_form_acceptance(): void
    {
        $application = $this->paymentApplication();
        app(ApplicationWorkflowService::class)->recordPaymentDecision($application, false, notes: 'The amount does not match.');
        $this->assertTrue($application->canSubmitPayment());
        $this->assertFalse($application->payment_verified);
        $this->assertEquals(ApplicationStatus::AwaitingPayment, $application->status);
        Mail::assertSent(TemplatedMail::class, fn ($mail) => str_contains($mail->resolvedBody, 'The amount does not match.'));
    }

    public function test_rejected_application_cannot_be_resurrected_by_stale_payment_processing(): void
    {
        $application = $this->paymentApplication();
        $stale = $application->fresh();
        app(ApplicationWorkflowService::class)->reject($application, 'Not eligible.');
        try {
            app(ApplicationWorkflowService::class)->recordPaymentDecision($stale, true, activeUntil: CarbonImmutable::now()->addYear());
            $this->fail('Rejected applications cannot be processed.');
        } catch (ValidationException) {
            $this->assertEquals(ApplicationStatus::Rejected, $application->fresh()->status);
        }
        Mail::assertSentCount(1);
    }

    public function test_information_correction_closes_the_payment_stage(): void
    {
        $application = $this->paymentApplication();
        $application->update(['company_name' => 'Corrected company']);
        $this->assertFalse($application->canSubmitPayment());
        $this->assertNull($application->admin_approved_at);
        $this->assertNull($application->payment_instructions);
        $this->assertFalse($application->documents_received);
        $this->assertFalse($application->physical_form_received);
        $this->assertEquals(ApplicationStatus::Submitted, $application->status);
    }

    public function test_processing_preserves_supplied_payment_details_and_supports_backdating(): void
    {
        $application = $this->paymentApplication(['payment_method' => 'pay_order', 'payment_date' => today()->subDays(5)]);
        app(ApplicationWorkflowService::class)->recordPaymentDecision($application, true,
            activeUntil: CarbonImmutable::now()->addYear(), processedAt: CarbonImmutable::today()->subDays(2));
        $this->assertEquals('pay_order', $application->payment_method->value);
        $this->assertEquals(today()->subDays(5)->toDateString(), $application->payment_date->toDateString());
        $this->assertEquals(today()->subDays(2)->toDateString(), $application->payment_processed_at->toDateString());
    }
}
