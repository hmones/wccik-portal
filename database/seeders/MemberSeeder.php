<?php

namespace Database\Seeders;

use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        Member::firstOrCreate(
            ['membership_number' => 'WCCIK-2025-0001'],
            [
                'membership_class' => MembershipClass::Corporate->value,
                'authorized_representative_name' => 'Ayesha Khan',
                'company_name' => 'Khan Textiles (Pvt.) Ltd.',
                'email' => 'ayesha@khantextiles.test',
                'website' => 'https://khantextiles.test',
                'established_year' => 2010,
                'industry' => Industry::Manufacturing->value,
                'company_classification' => CompanyClassification::PrivateLtd->value,
                'cnic' => '42101-1234567-8',
                'cnic_expiry_date' => '2032-04-15',
                'turnover_pkr' => 120_000_000,
                'employees_count' => 85,
                'ntn_number' => '1234567',
                'sales_tax_no' => '7654321',
                'address' => 'Plot 42, Sector 23, Korangi Industrial Area',
                'postal_code' => '74900',
                'district' => 'Karachi',
                'phone' => '+922135050505',
                'cell' => '+923001234567',
                'whatsapp' => '+923001234567',
                'alternate_no' => null,
                'other_chamber_memberships' => 'FPCCI, KCCI',
                'active_until' => now()->addMonths(6)->format('Y-m-d'),
            ],
        );

        Member::firstOrCreate(
            ['membership_number' => 'WCCIK-2025-0002'],
            [
                'membership_class' => MembershipClass::Associate->value,
                'authorized_representative_name' => 'Fatima Siddiqui',
                'company_name' => 'Siddiqui Trading Co.',
                'email' => 'fatima@siddiquitrading.test',
                'website' => null,
                'established_year' => 2018,
                'industry' => Industry::Trading->value,
                'company_classification' => CompanyClassification::Proprietorship->value,
                'cnic' => '42201-7654321-2',
                'cnic_expiry_date' => '2030-11-20',
                'turnover_pkr' => 15_000_000,
                'employees_count' => 8,
                'ntn_number' => null,
                'sales_tax_no' => null,
                'address' => 'Shop 7, Causeway Belt, Korangi',
                'postal_code' => '74900',
                'district' => 'Karachi',
                'phone' => null,
                'cell' => '+923337654321',
                'whatsapp' => '+923337654321',
                'alternate_no' => null,
                'other_chamber_memberships' => null,
                'active_until' => now()->subMonth()->format('Y-m-d'),
            ],
        );

        Member::firstOrCreate(
            ['membership_number' => 'WCCIK-2024-0045'],
            [
                'membership_class' => MembershipClass::Corporate->value,
                'authorized_representative_name' => 'Zara Ahmed',
                'company_name' => 'Ahmed Logistics Services (Pvt.) Ltd.',
                'email' => 'zara@ahmedlogistics.test',
                'website' => 'https://ahmedlogistics.test',
                'established_year' => 2015,
                'industry' => Industry::Services->value,
                'company_classification' => CompanyClassification::PrivateLtd->value,
                'cnic' => '42301-5551234-5',
                'cnic_expiry_date' => '2029-06-10',
                'turnover_pkr' => 65_000_000,
                'employees_count' => 32,
                'ntn_number' => '9876543',
                'sales_tax_no' => '3456789',
                'address' => 'Office 12, 2nd Floor, Mehran Town',
                'postal_code' => '74900',
                'district' => 'Karachi',
                'phone' => '+922136060606',
                'cell' => '+923215551234',
                'whatsapp' => null,
                'alternate_no' => '+923015551234',
                'other_chamber_memberships' => 'KATI',
                'active_until' => null, // never activated, e.g. awaiting first approval
            ],
        );
    }
}
