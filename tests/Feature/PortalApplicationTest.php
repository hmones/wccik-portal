<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Mail\TemplatedMail;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PortalApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function applicant(array $overrides = []): Applicant
    {
        return Applicant::create(array_merge([
            'email' => 'me@example.test',
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_apply_requires_sign_in(): void
    {
        $this->get(route('portal.apply'))->assertRedirect(route('portal.sign-in'));
    }

    public function test_apply_renders_empty_for_new_applicant(): void
    {
        $this->actingAs($this->applicant(), 'applicant')
            ->get(route('portal.apply'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PortalApply')
                ->where('draft', null)
                ->where('applicantEmail', 'me@example.test')
            );
    }

    public function test_autosave_does_not_create_row_for_empty_payload(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.apply.autosave'), [])
            ->assertOk()
            ->assertJson(['status' => 'noop']);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_autosave_materialises_draft_row_on_first_typed_field(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.apply.autosave'), [
                'company_name' => 'Draft Co.',
            ])
            ->assertOk()
            ->assertJson(['status' => 'saved']);

        $app = Application::firstOrFail();
        $this->assertEquals($applicant->id, $app->applicant_id);
        $this->assertEquals(ApplicationType::NewMember, $app->type);
        $this->assertEquals(ApplicationStatus::Draft, $app->status);
        $this->assertEquals('Draft Co.', $app->company_name);
        $this->assertEquals($applicant->email, $app->email);
    }

    public function test_autosave_updates_same_draft_on_subsequent_calls(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.apply.autosave'), ['company_name' => 'Draft Co.']);
        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.apply.autosave'), ['company_name' => 'Updated Co.']);

        $this->assertDatabaseCount('applications', 1);
        $this->assertEquals('Updated Co.', Application::first()->company_name);
    }

    public function test_autosave_clears_ntn_number_when_has_ntn_is_false(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.apply.autosave'), [
                'company_name' => 'C',
                'has_ntn' => true,
                'ntn_number' => '1234567',
            ]);
        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.apply.autosave'), [
                'has_ntn' => false,
                'ntn_number' => '1234567',
            ]);

        $this->assertNull(Application::first()->ntn_number);
    }

    public function test_submit_rejects_incomplete_payload(): void
    {
        $this->actingAs($this->applicant(), 'applicant')
            ->from(route('portal.apply'))
            ->post(route('portal.apply.submit'), ['has_ntn' => false])
            ->assertRedirect(route('portal.apply'))
            ->assertSessionHasErrors(['membership_class', 'industry', 'authorized_representative_name', 'cnic', 'company_name', 'terms_confirmed']);
    }

    public function test_submit_persists_submitted_status_and_timestamps(): void
    {
        Mail::fake();
        $applicant = $this->applicant();

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.apply.submit'), [
                'membership_class' => 'corporate',
                'industry' => 'services',
                'authorized_representative_name' => 'Jane Doe',
                'cnic' => '42101-1234567-8',
                'company_name' => 'Jane Co.',
                'company_classification' => 'proprietorship',
                'address' => '123 Main St',
                'district' => 'Karachi',
                'cell' => '+923001234567',
                'has_ntn' => false,
                'terms_confirmed' => true,
            ])->assertRedirect(route('portal.home'));

        $app = Application::firstOrFail();
        $this->assertEquals($applicant->id, $app->applicant_id);
        $this->assertEquals(ApplicationStatus::Submitted, $app->status);
        $this->assertNotNull($app->submitted_at);
        $this->assertNotNull($app->terms_confirmed_at);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($applicant) {
            $this->assertNotEmpty($mail->resolvedAttachments);
            $this->assertEquals('application/pdf', $mail->resolvedAttachments[0]['mime']);

            return $mail->hasTo($applicant->email);
        });
    }

    public function test_show_redirects_to_dashboard_when_application_is_past_draft(): void
    {
        $applicant = $this->applicant();
        Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
            'authorized_representative_name' => 'Jane',
            'email' => $applicant->email,
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.apply'))
            ->assertRedirect(route('portal.home'));
    }

    public function test_dashboard_shows_active_application(): void
    {
        $applicant = $this->applicant();
        Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
            'authorized_representative_name' => 'Jane',
            'company_name' => 'Jane Co.',
            'email' => $applicant->email,
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PortalDashboard')
                ->where('journey.state', 'application_in_progress')
                ->where('journey.application.company_name', 'Jane Co.')
                ->where('journey.application.status', 'submitted')
                ->where('journey.application.information_approved', false)
                ->where('journey.application.payment_verified', false)
                ->where('journey.application.physical_form_received', false)
                ->where('journey.application.documents_received', false)
            );
    }

    public function test_dashboard_shows_new_applicant_state_when_nothing_on_file(): void
    {
        $this->actingAs($this->applicant(), 'applicant')
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PortalDashboard')
                ->where('journey.state', 'new_applicant')
            );
    }

    public function test_dashboard_shows_active_member_state(): void
    {
        $member = Member::factory()->create(['active_until' => now()->addMonths(3)]);
        $applicant = Applicant::create([
            'email' => $member->email,
            'member_id' => $member->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PortalDashboard')
                ->where('journey.state', 'active_member')
                ->where('journey.member.membership_number', $member->membership_number)
            );
    }

    public function test_dashboard_shows_expired_member_state(): void
    {
        $member = Member::factory()->expired()->create();
        $applicant = Applicant::create([
            'email' => $member->email,
            'member_id' => $member->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PortalDashboard')
                ->where('journey.state', 'expired_member')
            );
    }

    public function test_linked_member_cannot_access_apply(): void
    {
        $member = Member::factory()->create();
        $applicant = Applicant::create([
            'email' => $member->email,
            'member_id' => $member->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.apply'))
            ->assertRedirect(route('portal.home'));
    }

    public function test_terminal_applications_do_not_block_new_ones(): void
    {
        $applicant = $this->applicant();
        Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Rejected,
            'authorized_representative_name' => 'Jane',
            'email' => $applicant->email,
            'cnic' => '42101-1234567-8',
            'cell' => '+923001234567',
            'submitted_at' => now()->subMonth(),
        ]);

        // Active lookup should ignore the rejected one; applicant can start fresh.
        $this->assertNull($applicant->fresh()->activeApplication());

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.apply'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('PortalApply')->where('draft', null));
    }

    public function test_submitted_application_rejects_stale_autosave_and_repeat_submission(): void
    {
        $applicant = $this->applicant();
        $application = Application::factory()->create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'email' => $applicant->email,
            'company_name' => 'Submitted company',
        ]);
        $this->actingAs($applicant, 'applicant')->postJson(route('portal.apply.autosave'), [
            'company_name' => 'Unexpected change',
        ])->assertForbidden();
        $this->actingAs($applicant, 'applicant')->post(route('portal.apply.submit'), [
            'membership_class' => 'corporate',
            'industry' => 'services',
            'authorized_representative_name' => 'Jane Doe',
            'cnic' => '42101-1234567-8',
            'company_name' => 'Unexpected change',
            'company_classification' => 'proprietorship',
            'address' => '123 Main St',
            'district' => 'Karachi',
            'cell' => '+923001234567',
            'email' => $applicant->email,
            'has_ntn' => false,
            'terms_confirmed' => true,
        ])->assertForbidden();
        $this->assertDatabaseCount('applications', 1);
        $this->assertEquals('Submitted company', $application->fresh()->company_name);
    }
}
