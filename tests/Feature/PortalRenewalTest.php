<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalRenewalTest extends TestCase
{
    use RefreshDatabase;

    private function makeMember(array $overrides = []): Member
    {
        // Default: expired, so renewal is allowed by the controller gate.
        return Member::factory()->create(array_merge([
            'membership_number' => 'WCCIK-2025-8888',
            'email' => 'renewer@example.test',
            'authorized_representative_name' => 'Renewal Member',
            'company_name' => 'Member Co.',
            'cnic' => '42101-9876543-2',
            'cell' => '+923009998877',
            'membership_class' => MembershipClass::Corporate->value,
            'industry' => Industry::Services->value,
            'company_classification' => CompanyClassification::PrivateLtd->value,
            'active_until' => now()->subMonth()->format('Y-m-d'),
        ], $overrides));
    }

    private function linkedApplicant(Member $member): Applicant
    {
        return Applicant::create([
            'email' => $member->email,
            'member_id' => $member->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_portal_renew_requires_sign_in(): void
    {
        $this->get(route('portal.renew'))->assertRedirect(route('portal.sign-in'));
    }

    public function test_portal_renew_redirects_unlinked_applicant_to_dashboard(): void
    {
        $applicant = Applicant::create([
            'email' => 'no-member@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.renew'))
            ->assertRedirect(route('portal.home'));
    }

    public function test_portal_renew_redirects_active_member_to_dashboard(): void
    {
        $member = Member::factory()->create(['active_until' => now()->addMonths(6)]);
        $applicant = $this->linkedApplicant($member);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.renew'))
            ->assertRedirect(route('portal.home'));
    }

    public function test_portal_renew_prefills_member_data(): void
    {
        $member = $this->makeMember();
        $applicant = $this->linkedApplicant($member);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.renew'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PortalRenew')
                ->where('member.membership_number', $member->membership_number)
                ->where('member.company_name', $member->company_name)
                ->where('draft', null)
            );
    }

    public function test_autosave_rejects_unlinked_applicant(): void
    {
        $applicant = Applicant::create([
            'email' => 'no-member@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.renew.autosave'), ['company_name' => 'X'])
            ->assertStatus(403);
    }

    public function test_autosave_does_not_create_row_for_empty_payload(): void
    {
        $member = $this->makeMember();
        $applicant = $this->linkedApplicant($member);

        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.renew.autosave'), [])
            ->assertOk()
            ->assertJson(['status' => 'noop']);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_autosave_materialises_renewal_draft_linked_to_applicant_and_member(): void
    {
        $member = $this->makeMember();
        $applicant = $this->linkedApplicant($member);

        $this->actingAs($applicant, 'applicant')
            ->postJson(route('portal.renew.autosave'), [
                'company_name' => 'Updated Co.',
            ])
            ->assertOk()
            ->assertJson(['status' => 'saved']);

        $app = Application::firstOrFail();
        $this->assertEquals(ApplicationType::Renewal, $app->type);
        $this->assertEquals(ApplicationStatus::Draft, $app->status);
        $this->assertEquals($applicant->id, $app->applicant_id);
        $this->assertEquals($member->membership_number, $app->existing_membership_number);
    }

    public function test_submit_requires_core_fields(): void
    {
        $member = $this->makeMember();
        $applicant = $this->linkedApplicant($member);

        // Payment proof is now optional (applicants can hand a cheque to the
        // office or upload it later from the dashboard), so it is no longer
        // in the required-fields list.
        $this->actingAs($applicant, 'applicant')
            ->from(route('portal.renew'))
            ->post(route('portal.renew.submit'), [])
            ->assertRedirect(route('portal.renew'))
            ->assertSessionHasErrors(['membership_class', 'industry', 'authorized_representative_name', 'terms_confirmed'])
            ->assertSessionDoesntHaveErrors('payment_proof');
    }

    public function test_submit_persists_renewal_with_payment_proof(): void
    {
        Storage::fake('public');
        $member = $this->makeMember();
        $applicant = $this->linkedApplicant($member);

        $file = UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf');

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.renew.submit'), [
                'membership_class' => 'corporate',
                'industry' => 'services',
                'authorized_representative_name' => 'Updated Rep',
                'company_name' => 'Updated Co.',
                'email' => 'updated@example.test',
                'website' => 'https://example.test',
                'established_year' => 2015,
                'company_classification' => 'private_ltd',
                'cnic' => '42101-9876543-2',
                'cnic_expiry_date' => '2031-05-10',
                'turnover_pkr' => 25_000_000,
                'employees_count' => 20,
                'ntn_number' => '1234567',
                'sales_tax_no' => '7654321',
                'address' => 'New Address, Karachi',
                'postal_code' => '74900',
                'district' => 'Karachi',
                'phone' => '+922136060606',
                'cell' => '+923001112233',
                'whatsapp' => '+923001112233',
                'alternate_no' => '',
                'other_chamber_memberships' => '',
                'payment_proof' => $file,
                'terms_confirmed' => true,
            ])->assertRedirect(route('portal.home'));

        $app = Application::firstOrFail();
        $this->assertEquals(ApplicationType::Renewal, $app->type);
        $this->assertEquals(ApplicationStatus::Submitted, $app->status);
        $this->assertEquals($member->membership_number, $app->existing_membership_number);
        $this->assertNotNull($app->payment_proof_path);
        Storage::disk('public')->assertExists($app->payment_proof_path);
    }

    public function test_portal_renew_redirects_to_dashboard_when_renewal_already_submitted(): void
    {
        $member = $this->makeMember();
        $applicant = $this->linkedApplicant($member);
        Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::Renewal,
            'status' => ApplicationStatus::Submitted,
            'existing_membership_number' => $member->membership_number,
            'company_name' => 'Co.',
            'authorized_representative_name' => 'Rep',
            'email' => $member->email,
            'cnic' => '42101-9876543-2',
            'cell' => '+923009998877',
            'submitted_at' => now(),
        ]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.renew'))
            ->assertRedirect(route('portal.home'));
    }
}
