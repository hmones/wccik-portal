<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case AwaitingDocuments = 'awaiting_documents';
    case AwaitingPayment = 'awaiting_payment';
    case ReadyForApproval = 'ready_for_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::AwaitingDocuments => 'Awaiting Documents',
            self::AwaitingPayment => 'Awaiting Payment',
            self::ReadyForApproval => 'Ready for Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Submitted => 'info',
            self::AwaitingDocuments, self::AwaitingPayment => 'warning',
            self::ReadyForApproval, self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Statuses that count as "active" — an applicant may have only one
     * application in these states at a time.
     *
     * @return array<self>
     */
    public static function activeStates(): array
    {
        return [
            self::Draft,
            self::Submitted,
            self::AwaitingDocuments,
            self::AwaitingPayment,
            self::ReadyForApproval,
        ];
    }

    public function isActive(): bool
    {
        return in_array($this, self::activeStates(), strict: true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Approved || $this === self::Rejected;
    }
}
