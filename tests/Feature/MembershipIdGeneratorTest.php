<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Applicant;
use App\Models\Application;
use App\Services\Membership\MembershipIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipIdGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generator_produces_wccik_year_four_digit_format(): void
    {
        $id = app(MembershipIdGenerator::class)->generate();

        $this->assertMatchesRegularExpression('/^WCCIK-\d{4}-\d{4}$/', $id);
    }

    public function test_application_gets_a_membership_id_assigned_on_create(): void
    {
        $applicant = Applicant::create([
            'email' => 'auto@example.test',
            'email_verified_at' => now(),
        ]);

        $app = Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Draft,
            'authorized_representative_name' => 'Jane Doe',
            'email' => $applicant->email,
            'cell' => '+923001234567',
        ]);

        $this->assertNotEmpty($app->membership_id);
        $this->assertMatchesRegularExpression('/^WCCIK-\d{4}-\d{4}$/', $app->membership_id);
    }

    public function test_membership_id_cannot_be_overwritten_after_creation(): void
    {
        $applicant = Applicant::create([
            'email' => 'immutable@example.test',
            'email_verified_at' => now(),
        ]);

        $app = Application::create([
            'applicant_id' => $applicant->id,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Draft,
            'authorized_representative_name' => 'Jane Doe',
            'email' => $applicant->email,
            'cell' => '+923001234567',
        ]);
        $originalId = $app->membership_id;

        $app->update(['membership_id' => 'WCCIK-9999-0001']);

        $this->assertEquals($originalId, $app->fresh()->membership_id);
    }

    public function test_two_applications_get_different_membership_ids(): void
    {
        $a1 = Application::create([
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Draft,
            'authorized_representative_name' => 'Jane',
            'email' => 'a@example.test',
            'cell' => '+923001234567',
        ]);
        $a2 = Application::create([
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Draft,
            'authorized_representative_name' => 'Bob',
            'email' => 'b@example.test',
            'cell' => '+923009999999',
        ]);

        $this->assertNotEquals($a1->membership_id, $a2->membership_id);
    }
}
