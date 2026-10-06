<?php

namespace App\Services\Portal;

use App\Models\ApplicantOtp;
use App\Services\Mail\TemplatedMailService;
use Illuminate\Support\Facades\Hash;

class ApplicantOtpService
{
    public const CODE_LENGTH = 6;

    public const EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly TemplatedMailService $mailer) {}

    public function issue(string $email, ?string $requestIp = null): ApplicantOtp
    {
        ApplicantOtp::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        $otp = ApplicantOtp::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'request_ip' => $requestIp,
        ]);

        $this->mailer->send('applicant_login_otp', $email, [
            'code' => $code,
            'expires_in_minutes' => self::EXPIRY_MINUTES,
            'email' => $email,
        ]);

        return $otp;
    }

    public function verify(string $email, string $code): bool
    {
        $otp = ApplicantOtp::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($otp === null || $otp->isExpired()) {
            return false;
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->update(['consumed_at' => now()]);

            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 10 ** self::CODE_LENGTH - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
