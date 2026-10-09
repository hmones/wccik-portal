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
            'physical_form_received' => true,
            'documents_received' => true,
            'admin_approved_at' => now(),
            'payment_instructions' => 'Test payment instructions',
        ]);

        return [$applicant, $application];
    }

    public function test_upload_requires_sign_in(): void
    {
        $this->post(route('portal.application.payment-proof'))
            ->assertRedirect(route('portal.sign-in'));
    }

    public function test_upload_stores_file_without_treating_the_receipt_as_verified(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::AwaitingPayment);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.application.payment-proof'), [
                'payment_date' => today()->toDateString(),
                'payment_method' => 'bank_transfer',
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('portal.home'))
            ->assertSessionHas('status', 'payment_proof_uploaded');

        $application->refresh();
        $this->assertEquals(ApplicationStatus::ReadyForApproval, $application->status);
        $this->assertFalse($application->payment_verified);
        $this->assertEquals(today()->toDateString(), $application->payment_date->toDateString());
        $this->assertEquals('bank_transfer', $application->payment_method->value);
        $this->assertNotNull($application->payment_proof_path);
        Storage::disk('public')->assertExists($application->payment_proof_path);
    }

    public function test_upload_preserves_status_when_already_submitted_or_awaiting_documents(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::Submitted);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.application.payment-proof'), [
                'payment_date' => today()->toDateString(),
                'payment_method' => 'bank_transfer',
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('portal.home'));

        $application->refresh();
        $this->assertEquals(ApplicationStatus::ReadyForApproval, $application->status);
        $this->assertNotNull($application->payment_proof_path);
    }

    public function test_upload_replaces_existing_file(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::AwaitingPayment);
        $application->update(['payment_proof_path' => 'payment-proofs/old.pdf']);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.application.payment-proof'), [
                'payment_date' => today()->toDateString(),
                'payment_method' => 'bank_transfer',
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
                'payment_date' => today()->toDateString(),
                'payment_method' => 'bank_transfer',
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
                'payment_date' => today()->toDateString(),
                'payment_method' => 'bank_transfer',
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
                'payment_date' => today()->toDateString(),
                'payment_method' => 'bank_transfer',
                'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertNull($application->fresh()->payment_proof_path);
    }

    public function test_replacing_verified_receipt_resets_payment_but_preserves_information_approval(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::ReadyForApproval);
        $application->update(['payment_proof_path' => 'payment-proofs/old.pdf']);
        $application->update([
            'payment_date' => now(),
            'admin_approved_at' => now(),
            'admin_approved_until' => now()->addYear(),
        ]);
        $application->update(['payment_verified' => true]);
        $this->actingAs($applicant, 'applicant')->post(route('portal.application.payment-proof'), [
            'payment_date' => today()->toDateString(),
            'payment_method' => 'bank_transfer',
            'payment_proof' => UploadedFile::fake()->create('new.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('portal.home'));
        $application->refresh();
        $this->assertFalse($application->payment_verified);
        $this->assertEquals(today()->toDateString(), $application->payment_date->toDateString());
        $this->assertEquals('bank_transfer', $application->payment_method->value);
        $this->assertNotNull($application->admin_approved_at);
        $this->assertEquals(ApplicationStatus::ReadyForApproval, $application->status);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_upload_requires_payment_date_and_method_even_when_receipt_exists(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::Submitted);
        $application->update(['payment_proof_path' => 'old.pdf', 'payment_date' => today(), 'payment_method' => 'cheque']);
        $this->actingAs($applicant, 'applicant')->post(route('portal.application.payment-proof'), [
            'payment_proof' => UploadedFile::fake()->create('new.pdf', 120, 'application/pdf'),
        ])->assertSessionHasErrors(['payment_date', 'payment_method']);
        $this->assertEquals('old.pdf', $application->fresh()->payment_proof_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_upload_rejects_future_date_and_unknown_payment_method(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::Submitted);
        $this->actingAs($applicant, 'applicant')->post(route('portal.application.payment-proof'), [
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            'payment_date' => today()->addDay()->toDateString(),
            'payment_method' => 'unsupported',
        ])->assertSessionHasErrors(['payment_date', 'payment_method']);
        $this->assertNull($application->fresh()->payment_proof_path);
    }

    public function test_upload_is_forbidden_until_the_form_is_accepted(): void
    {
        Storage::fake('public');
        [$applicant, $application] = $this->applicantWithApp(ApplicationStatus::Submitted);
        $application->update(['admin_approved_at' => null, 'payment_instructions' => null]);
        $this->actingAs($applicant, 'applicant')->post(route('portal.application.payment-proof'), [
            'payment_date' => today()->toDateString(),
            'payment_method' => 'bank_transfer',
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
        ])->assertForbidden();
        $this->assertNull($application->fresh()->payment_proof_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }
}
