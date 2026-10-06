<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\RequestLoginOtpRequest;
use App\Http\Requests\Portal\VerifyLoginOtpRequest;
use App\Models\Applicant;
use App\Models\Member;
use App\Services\Portal\ApplicantOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PortalAuthController extends Controller
{
    public function signInPage(): Response
    {
        return Inertia::render('PortalSignIn');
    }

    public function requestOtp(RequestLoginOtpRequest $request, ApplicantOtpService $otp): RedirectResponse
    {
        $email = $request->string('email');

        $otp->issue($email, $request->ip());

        $request->session()->put('portal.pending_email', $email);

        return redirect()->route('portal.verify');
    }

    public function verifyPage(Request $request): RedirectResponse|Response
    {
        $email = $request->session()->get('portal.pending_email');

        if ($email === null) {
            return redirect()->route('portal.sign-in');
        }

        return Inertia::render('PortalVerify', [
            'maskedEmail' => $this->maskEmail($email),
        ]);
    }

    public function verify(VerifyLoginOtpRequest $request, ApplicantOtpService $otp): RedirectResponse
    {
        $email = $request->session()->get('portal.pending_email');

        if ($email === null) {
            return redirect()->route('portal.sign-in');
        }

        if (! $otp->verify($email, $request->string('code'))) {
            return back()->withErrors([
                'code' => 'That code is invalid or has expired. Request a new one if needed.',
            ]);
        }

        $applicant = Applicant::firstOrCreate(
            ['email' => $email],
            ['email_verified_at' => now()],
        );

        // If this applicant isn't linked to a Member yet, see if their email
        // matches a WCCIK member record. OTP verification just proved they
        // own the inbox, which is proof enough to claim that identity.
        if ($applicant->member_id === null) {
            $member = Member::where('email', $email)->first();
            if ($member !== null) {
                $applicant->member_id = $member->id;
            }
        }

        $applicant->forceFill([
            'email_verified_at' => $applicant->email_verified_at ?? now(),
            'last_signed_in_at' => now(),
        ])->save();

        $request->session()->forget('portal.pending_email');

        Auth::guard('applicant')->login($applicant, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('portal.home'));
    }

    public function resend(Request $request, ApplicantOtpService $otp): RedirectResponse
    {
        $email = $request->session()->get('portal.pending_email');

        if ($email === null) {
            return redirect()->route('portal.sign-in');
        }

        $otp->issue($email, $request->ip());

        return back()->with('status', 'A new sign-in code has been sent.');
    }

    /**
     * One sign-out, everywhere. Rotates the remember token so any other
     * device the applicant was signed in on is also kicked out.
     */
    public function signOut(Request $request): RedirectResponse
    {
        $applicant = Auth::guard('applicant')->user();

        if ($applicant !== null) {
            $applicant->setRememberToken(Str::random(60));
            $applicant->save();
        }

        Auth::guard('applicant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.sign-in');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $visible = mb_substr($local, 0, 2);
        $maskedLocal = $visible.str_repeat('*', max(1, mb_strlen($local) - 2));

        return $maskedLocal.'@'.$domain;
    }
}
