<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Applicant;
use App\Models\Member;
use App\Services\Portal\ApplicantOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PortalAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_renders_for_guests(): void
    {
        $this->get(route('portal.sign-in'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('PortalSignIn'));
    }

    public function test_requesting_otp_sends_email_and_sets_pending(): void
    {
        Mail::fake();

        $this->post(route('portal.sign-in.submit'), ['email' => 'User@Example.Test'])
            ->assertRedirect(route('portal.verify'));

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => $mail->hasTo('user@example.test'));
        $this->assertEquals('user@example.test', session('portal.pending_email'));
        $this->assertDatabaseCount('applicant_otps', 1);
    }

    public function test_requesting_otp_requires_email(): void
    {
        $this->from(route('portal.sign-in'))
            ->post(route('portal.sign-in.submit'), [])
            ->assertRedirect(route('portal.sign-in'))
            ->assertSessionHasErrors('email');
    }

    public function test_verify_page_redirects_without_pending_email(): void
    {
        $this->get(route('portal.verify'))->assertRedirect(route('portal.sign-in'));
    }

    public function test_verifying_correct_code_signs_applicant_in(): void
    {
        Mail::fake();
        app(ApplicantOtpService::class)->issue('new@example.test');
        $code = $this->extractCodeFromSentMail();

        $this->withSession(['portal.pending_email' => 'new@example.test'])
            ->post(route('portal.verify.submit'), ['code' => $code])
            ->assertRedirect(route('portal.home'));

        $this->assertAuthenticatedAs(Applicant::where('email', 'new@example.test')->firstOrFail(), 'applicant');
        $this->assertNotNull(Applicant::where('email', 'new@example.test')->first()?->last_signed_in_at);
    }

    public function test_verifying_creates_applicant_if_new(): void
    {
        Mail::fake();
        app(ApplicantOtpService::class)->issue('brand@new.test');
        $code = $this->extractCodeFromSentMail();

        $this->assertDatabaseMissing('applicants', ['email' => 'brand@new.test']);

        $this->withSession(['portal.pending_email' => 'brand@new.test'])
            ->post(route('portal.verify.submit'), ['code' => $code])
            ->assertRedirect(route('portal.home'));

        $this->assertDatabaseHas('applicants', ['email' => 'brand@new.test']);
    }

    public function test_verifying_wrong_code_rejects(): void
    {
        Mail::fake();
        app(ApplicantOtpService::class)->issue('user@example.test');

        $this->withSession(['portal.pending_email' => 'user@example.test'])
            ->from(route('portal.verify'))
            ->post(route('portal.verify.submit'), ['code' => '000000'])
            ->assertRedirect(route('portal.verify'))
            ->assertSessionHasErrors('code');

        $this->assertGuest('applicant');
    }

    public function test_dashboard_requires_sign_in(): void
    {
        $this->get(route('portal.home'))->assertRedirect(route('portal.sign-in'));
    }

    public function test_dashboard_renders_for_signed_in_applicant(): void
    {
        $applicant = Applicant::create(['email' => 'me@example.test', 'email_verified_at' => now()]);

        $this->actingAs($applicant, 'applicant')
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('PortalDashboard')
                ->where('applicant.email', 'me@example.test')
            );
    }

    public function test_sign_out_clears_session_and_rotates_remember_token(): void
    {
        $applicant = Applicant::create([
            'email' => 'me@example.test',
            'email_verified_at' => now(),
            'remember_token' => 'original-token',
        ]);

        $this->actingAs($applicant, 'applicant')
            ->post(route('portal.sign-out'))
            ->assertRedirect(route('portal.sign-in'));

        $this->assertGuest('applicant');
        $this->assertNotEquals('original-token', $applicant->fresh()->remember_token);
    }

    public function test_verifying_auto_links_applicant_to_matching_member(): void
    {
        Mail::fake();
        $member = Member::factory()->create(['email' => 'linked@example.test']);

        app(ApplicantOtpService::class)->issue('linked@example.test');
        $code = $this->extractCodeFromSentMail();

        $this->withSession(['portal.pending_email' => 'linked@example.test'])
            ->post(route('portal.verify.submit'), ['code' => $code])
            ->assertRedirect(route('portal.home'));

        $applicant = Applicant::where('email', 'linked@example.test')->firstOrFail();
        $this->assertEquals($member->id, $applicant->member_id);
    }

    public function test_verifying_does_not_link_when_no_member_shares_the_email(): void
    {
        Mail::fake();
        Member::factory()->create(['email' => 'someone-else@example.test']);

        app(ApplicantOtpService::class)->issue('fresh@example.test');
        $code = $this->extractCodeFromSentMail();

        $this->withSession(['portal.pending_email' => 'fresh@example.test'])
            ->post(route('portal.verify.submit'), ['code' => $code])
            ->assertRedirect(route('portal.home'));

        $applicant = Applicant::where('email', 'fresh@example.test')->firstOrFail();
        $this->assertNull($applicant->member_id);
    }

    public function test_admin_login_now_lives_under_admin(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/login')->assertNotFound();
    }

    private function extractCodeFromSentMail(): string
    {
        $code = null;
        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use (&$code) {
            if (preg_match('/\b(\d{6})\b/', $mail->resolvedBody, $m)) {
                $code = $m[1];
            }

            return true;
        });

        $this->assertIsString($code, 'Could not find a 6-digit code in the sent mail body.');

        return $code;
    }
}
