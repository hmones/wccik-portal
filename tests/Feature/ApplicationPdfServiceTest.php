<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Models\Applicant;
use App\Models\Application;
use App\Services\Application\ApplicationPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function newMemberApp(): Application
    {
        $applicant = Applicant::create([
            'email' => 'jane@example.test',
            'email_verified_at' => now(),
        ]);

        return Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
            'membership_class' => MembershipClass::Corporate->value,
            'industry' => Industry::Services->value,
            'company_classification' => CompanyClassification::PrivateLtd->value,
            'authorized_representative_name' => 'Jane Doe',
            'company_name' => 'Jane Co.',
            'email' => 'jane@example.test',
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now(),
        ]);
    }

    public function test_generates_valid_pdf_bytes_for_new_member_application(): void
    {
        $bytes = app(ApplicationPdfService::class)->pdfBytesFor($this->newMemberApp());

        $this->assertIsString($bytes);
        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertGreaterThan(5000, strlen($bytes));
    }

    public function test_generates_valid_pdf_bytes_for_renewal_application(): void
    {
        $applicant = Applicant::create([
            'email' => 'jane@example.test',
            'email_verified_at' => now(),
        ]);
        $application = Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::Renewal,
            'status' => ApplicationStatus::Submitted,
            'existing_membership_number' => 'WCCIK-2025-0001',
            'authorized_representative_name' => 'Jane Doe',
            'company_name' => 'Jane Co.',
            'email' => 'jane@example.test',
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now(),
        ]);

        $bytes = app(ApplicationPdfService::class)->pdfBytesFor($application);

        $this->assertStringStartsWith('%PDF-', $bytes);
    }

    public function test_filename_uses_application_type_and_slug(): void
    {
        $app = $this->newMemberApp();
        $filename = app(ApplicationPdfService::class)->filename($app);

        $this->assertStringContainsString('wccik-new-member', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function test_download_endpoint_streams_pdf_for_signed_in_applicant(): void
    {
        $app = $this->newMemberApp();

        $this->actingAs($app->applicant, 'applicant')
            ->get(route('portal.application.pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_download_endpoint_404s_when_applicant_has_no_application(): void
    {
        $applicant = Applicant::create([
            'email' => 'empty@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.application.pdf'))
            ->assertNotFound();
    }

    public function test_download_endpoint_requires_sign_in(): void
    {
        $this->get(route('portal.application.pdf'))->assertRedirect(route('portal.sign-in'));
    }
}
