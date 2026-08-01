<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case AwaitingDocuments = 'awaiting_documents';
    case AwaitingPayment = 'awaiting_payment';
    case ReadyForApproval = 'ready_for_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
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
            self::Submitted => 'info',
            self::AwaitingDocuments, self::AwaitingPayment => 'warning',
            self::ReadyForApproval, self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
