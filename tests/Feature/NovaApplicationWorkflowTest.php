<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Nova\Actions\VerifyPayment;
use App\Nova\Application as ApplicationResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class NovaApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_four_review_actions_are_exposed_with_prerequisite_checks(): void
    {
        $application = Application::factory()->create();
        $resource = new ApplicationResource($application);
        $request = NovaRequest::create('/nova-api/applications/'.$application->id);
        $actions = $resource->actions($request);
        $this->assertEquals(['Accept form and request payment', 'Process payment and create membership', 'Resend payment instructions', 'Reject application'], array_map(fn ($action) => $action->name(), $actions));
        $this->assertFalse($actions[0]->authorizedToRun($request, $application));
        $this->assertFalse($actions[1]->authorizedToRun($request, $application));
        $this->assertFalse($actions[2]->authorizedToRun($request, $application));
        $application->update(['physical_form_received' => true, 'documents_received' => true, 'payment_proof_path' => 'receipt.pdf']);
        $this->assertTrue($actions[0]->authorizedToRun($request, $application));
        $this->assertFalse($actions[1]->authorizedToRun($request, $application));
        $application->update(['admin_approved_at' => now(), 'payment_instructions' => 'Test payment instructions']);
        $this->assertTrue($actions[1]->authorizedToRun($request, $application));
        $application->update(['status' => ApplicationStatus::Rejected]);
        foreach ($actions as $action) {
            $this->assertFalse($action->authorizedToRun($request, $application));
        }
    }

    public function test_nova_update_cannot_override_status_or_payment_verification(): void
    {
        Mail::fake();
        Gate::define('viewNova', fn () => true);
        $application = Application::factory()->create([
            'membership_class' => 'corporate',
            'industry' => 'services',
        ]);
        $this->actingAs(User::factory()->create())->putJson('/nova-api/applications/'.$application->id, [
            ...array_intersect_key($application->toArray(), array_flip(['authorized_representative_name', 'email', 'cnic', 'cell', 'whatsapp', 'company_name', 'company_classification', 'address', 'district', 'membership_class', 'industry'])),
            'status' => 'approved',
            'payment_verified' => true,
            'admin_approved_at' => now(),
            'payment_instructions' => 'Test payment instructions',
        ])->assertOk();
        $this->assertEquals(ApplicationStatus::Submitted, $application->fresh()->status);
        $this->assertFalse($application->fresh()->payment_verified);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_admin_can_upload_an_office_receipt_without_approving_payment(): void
    {
        Storage::fake('public');
        Mail::fake();
        Gate::define('viewNova', fn () => true);
        $application = Application::factory()->create([
            'membership_class' => 'corporate',
            'industry' => 'services',
            'physical_form_received' => true,
            'documents_received' => true,
            'admin_approved_at' => now(),
            'payment_instructions' => 'Test payment instructions',
            'admin_approved_until' => now()->addYear(),
        ]);
        $this->actingAs(User::factory()->create())->put('/nova-api/applications/'.$application->id, [
            ...array_intersect_key($application->toArray(), array_flip(['authorized_representative_name', 'email', 'cnic', 'cell', 'whatsapp', 'company_name', 'company_classification', 'address', 'district', 'membership_class', 'industry'])),
            'physical_form_received' => true,
            'documents_received' => true,
            'payment_date' => today()->toDateString(),
            'payment_method' => 'cheque',
            'payment_proof_path' => UploadedFile::fake()->create('office-receipt.pdf', 120, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $application->refresh();
        Storage::disk('public')->assertExists($application->payment_proof_path);
        $this->assertFalse($application->payment_verified);
        $this->assertEquals(today()->toDateString(), $application->payment_date->toDateString());
        $this->assertEquals('cheque', $application->payment_method->value);
        $this->assertNotNull($application->admin_approved_at);
        $this->assertEquals(ApplicationStatus::ReadyForApproval, $application->status);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_payment_template_migration_preserves_customised_templates(): void
    {
        $legacy = EmailTemplate::create([
            'key' => 'application_awaiting_payment',
            'name' => 'Legacy payment email',
            'description' => 'Existing admin content',
            'subject_en' => 'Custom subject',
            'subject_ur' => 'Custom subject',
            'body_en' => 'Custom legacy body',
            'body_ur' => 'Custom legacy body',
            'available_variables' => [],
        ]);
        $neutral = EmailTemplate::where('key', 'application_payment_requested')->firstOrFail();
        $neutral->update(['body_en' => 'Custom neutral body']);
        $migration = require database_path('migrations/2026_10_08_000000_add_payment_request_email_template.php');
        $migration->up();
        $this->assertEquals('Custom legacy body', $legacy->fresh()->body_en);
        $this->assertEquals('Custom neutral body', $neutral->fresh()->body_en);
    }

    public function test_admin_can_amend_payment_details_and_verify_saved_values(): void
    {
        Mail::fake();
        Gate::define('viewNova', fn () => true);
        $application = Application::factory()->create([
            'membership_class' => 'corporate',
            'industry' => 'services',
            'physical_form_received' => true,
            'documents_received' => true,
            'payment_proof_path' => 'receipt.pdf',
            'payment_date' => today()->subDay(),
            'payment_method' => 'bank_transfer',
            'payment_verified' => true,
            'admin_approved_at' => now(),
            'payment_instructions' => 'Test payment instructions',
        ]);
        $date = today()->subDays(2)->toDateString();
        $this->actingAs(User::factory()->create())->putJson('/nova-api/applications/'.$application->id, [
            ...array_intersect_key($application->toArray(), array_flip(['authorized_representative_name', 'email', 'cnic', 'cell', 'whatsapp', 'company_name', 'company_classification', 'address', 'district', 'membership_class', 'industry'])),
            'physical_form_received' => true,
            'documents_received' => true,
            'payment_date' => $date,
            'payment_method' => 'cheque',
        ])->assertOk();
        $application->refresh();
        $this->assertFalse($application->payment_verified);
        $this->assertEquals($date, $application->payment_date->toDateString());
        $this->assertEquals('cheque', $application->payment_method->value);
        $this->assertTrue($application->physical_form_received);
        $this->assertTrue($application->documents_received);

        (new VerifyPayment)->handle(new ActionFields(collect(['verified' => true, 'notes' => null, 'active_until' => now()->addYear()->toDateString(), 'processed_at' => today()->toDateString()]), collect()), collect([$application]));
        $this->assertTrue($application->fresh()->payment_verified);
        $this->assertEquals($date, $application->payment_date->toDateString());
        $this->assertEquals('cheque', $application->payment_method->value);
    }
}
