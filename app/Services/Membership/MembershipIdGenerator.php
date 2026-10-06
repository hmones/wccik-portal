<?php

namespace App\Services\Membership;

use App\Models\Application;
use App\Models\Member;

/**
 * Issues the official membership ID that follows an application from creation
 * through approval.
 *
 * TODO: swap the faker-based numbering for the production rule when WCCIK
 * confirms the format they want (e.g. monotonic sequence per calendar year,
 * manual lookup, etc.). This class is the single place to change.
 */
class MembershipIdGenerator
{
    /**
     * Format: `WCCIK-YYYY-####` where #### is a zero-padded 4-digit number.
     * Guaranteed unique against both `applications.membership_id` and
     * `members.membership_number` by retrying on collision.
     */
    public function generate(): string
    {
        $year = (int) date('Y');

        do {
            $candidate = sprintf('WCCIK-%d-%04d', $year, fake()->numberBetween(1, 9999));
        } while ($this->isTaken($candidate));

        return $candidate;
    }

    private function isTaken(string $id): bool
    {
        return Application::where('membership_id', $id)->exists()
            || Member::where('membership_number', $id)->exists();
    }
}
