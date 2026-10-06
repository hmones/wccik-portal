<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Applicant;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofUploadTest extends TestCase
{
    use RefreshDatabase;

    private function applicantWithApp(ApplicationStatus $status): array
    {
        $applicant = Applicant::create([
            'email' => 'me@example.test',
            'email_verified_at' => now(),
        ]);

        $application = Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => $status,
            'authorized_representative_name' => 'Jane Doe',
            'company_name' => 'Jane Co.',
            'email' => $applicant->email,
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now(),
        ]);

        return [$applicant, $application];
    }

    public function test_upload_requires_sign_in(): void
    {
        $this->post(route('portal.application.payment-proof'))
            ->assertRedirect(route('portal.sign-in'));
    }

    public function test_upload_stores_file_and_transitions_from_awaiting_payment_to_ready(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::AwaitingPayment);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.application.payment-proof'), [
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('portal.home'))
            ->assertSessionHas('status', 'payment_proof_uploaded');

        $application->refresh();
        $this->assertEquals(ApplicationStatus::ReadyForApproval, $application->status);
        $this->assertNotNull($application->payment_proof_path);
        Storage::disk('public')->assertExists($application->payment_proof_path);
    }

    public function test_upload_preserves_status_when_already_submitted_or_awaiting_documents(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::Submitted);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.application.payment-proof'), [
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('portal.home'));

        $application->refresh();
        $this->assertEquals(ApplicationStatus::Submitted, $application->status);
        $this->assertNotNull($application->payment_proof_path);
    }

    public function test_upload_replaces_existing_file(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::AwaitingPayment);
        $application->update(['payment_proof_path' => 'payment-proofs/old.pdf']);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.application.payment-proof'), [
                'payment_proof' => UploadedFile::fake()->create('new.pdf', 120, 'application/pdf'),
            ]);

        $this->assertNotEquals('payment-proofs/old.pdf', $application->fresh()->payment_proof_path);
    }

    public function test_upload_rejects_oversize_file(): void
    {
        Storage::fake('public');
        [$applicant] = $this->applicantWithApp(ApplicationStatus::AwaitingPayment);

        $this->actingAs($applicant, 'applicant')
            ->from(route('portal.home'))
            ->post(route('portal.application.payment-proof'), [
                'payment_proof' => UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('payment_proof');
    }

    public function test_upload_rejects_unsupported_mime(): void
    {
        Storage::fake('public');
        [$applicant] = $this->applicantWithApp(ApplicationStatus::AwaitingPayment);

        $this->actingAs($applicant, 'applicant')
            ->from(route('portal.home'))
            ->post(route('portal.application.payment-proof'), [
                'payment_proof' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('payment_proof');
    }

    public function test_upload_bounces_when_application_is_already_approved(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::Approved);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.application.payment-proof'), [
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('portal.home'));

        $this->assertNull($application->fresh()->payment_proof_path);
    }
}
