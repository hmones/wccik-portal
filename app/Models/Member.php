<?php

namespace App\Models;

use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'membership_number',
        'membership_class',
        'authorized_representative_name',
        'company_name',
        'email',
        'website',
        'established_year',
        'industry',
        'company_classification',
        'cnic',
        'cnic_expiry_date',
        'turnover_pkr',
        'employees_count',
        'ntn_number',
        'sales_tax_no',
        'address',
        'postal_code',
        'district',
        'phone',
        'cell',
        'whatsapp',
        'alternate_no',
        'other_chamber_memberships',
        'active_until',
    ];

    protected $casts = [
        'membership_class' => MembershipClass::class,
        'company_classification' => CompanyClassification::class,
        'industry' => Industry::class,
        'cnic_expiry_date' => 'date',
        'active_until' => 'date',
    ];

    public function isActive(): bool
    {
        return $this->active_until !== null && $this->active_until->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->active_until !== null && $this->active_until->isPast();
    }
}
