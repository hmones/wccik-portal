<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Member;
use App\Services\Application\ApplicationPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentDetailsSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public static function applicationTypes(): array
    {
        return ['new membership' => [false], 'renewal' => [true]];
    }

    private function signInAndPayload(bool $renewal): array
    {
        Mail::fake();
        Storage::fake('public');
        $this->mock(ApplicationPdfService::class, function ($mock): void {
            $mock->shouldReceive('pdfBytesFor')->andReturn('%PDF-1.4 test');
            $mock->shouldReceive('filename')->andReturn('application.pdf');
        });
        $applicant = Applicant::create([
            'email' => 'payment-details@example.test',
            'email_verified_at' => now(),
            'member_id' => $renewal ? Member::factory()->expired()->create()->id : null,
        ]);
        $this->actingAs($applicant, 'applicant');

        return [
            'membership_class' => 'corporate',
            'industry' => 'services',
            'authorized_representative_name' => 'Jane Doe',
            'cnic' => '42101-1234567-8',
            'company_name' => 'Jane Co.',
            'company_classification' => 'proprietorship',
            'address' => '123 Main St',
            'district' => 'Karachi',
            'cell' => '+923001234567',
            'email' => $applicant->email,
            'has_ntn' => false,
            'terms_confirmed' => true,
        ];
    }

    #[DataProvider('applicationTypes')]
    public function test_receipt_requires_date_and_payment_method(bool $renewal): void
    {
        $payload = $this->signInAndPayload($renewal);
        $this->post(route($renewal ? 'portal.renew.submit' : 'portal.apply.submit'), [
            ...$payload,
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
        ])->assertSessionHasErrors($renewal ? ['payment_date', 'payment_method'] : ['payment_proof']);
        $this->assertDatabaseCount('applications', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    #[DataProvider('applicationTypes')]
    public function test_initial_payment_is_rejected_for_new_members_but_allowed_for_renewal(bool $renewal): void
    {
        $payload = $this->signInAndPayload($renewal);
        $date = today()->subDays(2)->toDateString();
        $response = $this->post(route($renewal ? 'portal.renew.submit' : 'portal.apply.submit'), [
            ...$payload,
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            'payment_date' => $date,
            'payment_method' => 'cheque',
        ]);
        if (! $renewal) {
            $response->assertSessionHasErrors(['payment_proof', 'payment_date', 'payment_method']);
            $this->assertDatabaseCount('applications', 0);
            $this->assertEmpty(Storage::disk('public')->allFiles());

            return;
        }
        $response->assertRedirect(route('portal.home'))->assertSessionHasNoErrors();
        $application = Application::firstOrFail();
        $this->assertEquals($date, $application->payment_date->toDateString());
        $this->assertEquals('cheque', $application->payment_method->value);
        $this->assertFalse($application->payment_verified);
        Storage::disk('public')->assertExists($application->payment_proof_path);
    }

    #[DataProvider('applicationTypes')]
    public function test_submission_without_payment_remains_optional(bool $renewal): void
    {
        $payload = $this->signInAndPayload($renewal);
        $this->post(route($renewal ? 'portal.renew.submit' : 'portal.apply.submit'), $payload)
            ->assertRedirect(route('portal.home'))->assertSessionHasNoErrors();
        $application = Application::firstOrFail();
        $this->assertNull($application->payment_proof_path);
        $this->assertNull($application->payment_date);
        $this->assertNull($application->payment_method);
    }
}
