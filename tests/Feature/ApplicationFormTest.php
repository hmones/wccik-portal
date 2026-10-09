<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFormTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // GET /apply
    // -------------------------------------------------------------------------

    public function test_apply_page_renders_for_guests(): void
    {
        $response = $this->get(route('apply'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Apply')
            ->has('captchaA')
            ->has('captchaB')
        );
    }

    public function test_apply_page_stores_captcha_answer_in_session(): void
    {
        $this->get(route('apply'));

        $this->assertNotNull(session('captcha_answer'));
        $answer = session('captcha_answer');
        $this->assertIsInt($answer);
        $this->assertGreaterThanOrEqual(2, $answer);
        $this->assertLessThanOrEqual(40, $answer);
    }

    // -------------------------------------------------------------------------
    // POST /apply, happy path
    // -------------------------------------------------------------------------

    public function test_valid_submission_creates_application_and_redirects(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), $this->validPayload(['captcha_answer' => 7]));

        $response->assertRedirect();
        $this->assertDatabaseCount('applications', 1);

        $application = Application::first();
        $this->assertEquals(ApplicationType::NewMember, $application->type);
        $this->assertEquals(ApplicationStatus::Submitted, $application->status);
        $this->assertEquals('Jane Doe', $application->authorized_representative_name);
        $this->assertNotNull($application->status_token);
        $this->assertNotNull($application->submitted_at);

        $response->assertRedirect(route('apply.confirmation', $application->status_token));
    }

    public function test_valid_submission_without_ntn_saves_correctly(): void
    {
        $this->withSession(['captcha_answer' => 5]);

        $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 5,
            'has_ntn' => false,
            'ntn_number' => '',
        ]));

        $application = Application::first();
        $this->assertFalse($application->has_ntn);
        $this->assertNull($application->ntn_number);
    }

    public function test_valid_submission_with_ntn_saves_ntn_number(): void
    {
        $this->withSession(['captcha_answer' => 5]);

        $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 5,
            'has_ntn' => true,
            'ntn_number' => '1234567',
        ]));

        $application = Application::first();
        $this->assertTrue($application->has_ntn);
        $this->assertEquals('1234567', $application->ntn_number);
    }

    // -------------------------------------------------------------------------
    // POST /apply, honeypot
    // -------------------------------------------------------------------------

    public function test_honeypot_filled_does_not_create_application(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), array_merge(
            $this->validPayload(['captcha_answer' => 7]),
            ['website' => 'http://spam.example.com']
        ));

        $response->assertRedirect();
        $this->assertDatabaseCount('applications', 0);
    }

    // -------------------------------------------------------------------------
    // POST /apply, validation failures
    // -------------------------------------------------------------------------

    public function test_missing_required_fields_returns_validation_errors(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), []);

        $response->assertSessionHasErrors([
            'authorized_representative_name',
            'cnic',
            'company_name',
            'company_classification',
            'address',
            'district',
            'cell',
            'email',
            'captcha_answer',
            'confirm_10_days',
        ]);
        $this->assertDatabaseCount('applications', 0);
    }

    public function test_invalid_cnic_format_returns_error(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 7,
            'cnic' => '12345678901234',
        ]));

        $response->assertSessionHasErrors('cnic');
    }

    public function test_valid_cnic_formats_are_accepted(): void
    {
        $this->withSession(['captcha_answer' => 5]);

        $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 5,
            'cnic' => '42201-1234567-3',
        ]));

        $this->assertDatabaseCount('applications', 1);
    }

    public function test_wrong_captcha_answer_returns_error(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 99,
        ]));

        $response->assertSessionHasErrors('captcha_answer');
        $this->assertDatabaseCount('applications', 0);
    }

    public function test_unchecked_10_day_confirmation_returns_error(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 7,
            'confirm_10_days' => false,
        ]));

        $response->assertSessionHasErrors('confirm_10_days');
        $this->assertDatabaseCount('applications', 0);
    }

    public function test_invalid_company_classification_returns_error(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 7,
            'company_classification' => 'not_a_valid_type',
        ]));

        $response->assertSessionHasErrors('company_classification');
    }

    public function test_ntn_required_when_has_ntn_is_true(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 7,
            'has_ntn' => true,
            'ntn_number' => '',
        ]));

        $response->assertSessionHasErrors('ntn_number');
        $this->assertDatabaseCount('applications', 0);
    }

    public function test_invalid_email_returns_error(): void
    {
        $this->withSession(['captcha_answer' => 7]);

        $response = $this->post(route('apply.store'), $this->validPayload([
            'captcha_answer' => 7,
            'email' => 'not-an-email',
        ]));

        $response->assertSessionHasErrors('email');
    }

    // -------------------------------------------------------------------------
    // GET /apply/confirmation/{token}
    // -------------------------------------------------------------------------

    public function test_confirmation_page_renders_for_valid_token(): void
    {
        $application = Application::factory()->create();

        $response = $this->get(route('apply.confirmation', $application->status_token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('ApplyConfirmation')
            ->where('token', (string) $application->status_token)
            ->where('name', $application->authorized_representative_name)
        );
    }

    public function test_confirmation_page_returns_404_for_invalid_token(): void
    {
        $response = $this->get(route('apply.confirmation', 'nonexistent-token'));

        $response->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'website' => '',
            'authorized_representative_name' => 'Jane Doe',
            'cnic' => '42201-1234567-3',
            'company_name' => 'Doe Enterprises',
            'company_classification' => 'proprietorship',
            'address' => '123 Main Street, Clifton',
            'district' => 'Karachi',
            'cell' => '0300-1234567',
            'whatsapp' => '',
            'phone' => '',
            'email' => 'jane@example.com',
            'has_ntn' => false,
            'ntn_number' => '',
            'captcha_answer' => 7,
            'confirm_10_days' => true,
        ], $overrides);
    }
}
