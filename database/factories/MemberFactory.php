<?php

namespace Database\Factories;

use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'membership_number' => 'WCCIK-'.$this->faker->year().'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'membership_class' => $this->faker->randomElement(MembershipClass::cases())->value,
            'authorized_representative_name' => $this->faker->name('female'),
            'company_name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'website' => 'https://'.$this->faker->domainName(),
            'established_year' => $this->faker->numberBetween(1990, 2024),
            'industry' => $this->faker->randomElement(Industry::cases())->value,
            'company_classification' => $this->faker->randomElement(CompanyClassification::cases())->value,
            'cnic' => sprintf('%05d-%07d-%d', $this->faker->numberBetween(10000, 99999), $this->faker->numberBetween(1000000, 9999999), $this->faker->numberBetween(0, 9)),
            'cnic_expiry_date' => $this->faker->dateTimeBetween('+1 year', '+10 years')->format('Y-m-d'),
            'turnover_pkr' => $this->faker->numberBetween(5_000_000, 500_000_000),
            'employees_count' => $this->faker->numberBetween(5, 500),
            'ntn_number' => (string) $this->faker->numberBetween(1000000, 9999999),
            'sales_tax_no' => (string) $this->faker->numberBetween(1000000, 9999999),
            'address' => $this->faker->streetAddress(),
            'postal_code' => (string) $this->faker->numberBetween(10000, 99999),
            'district' => 'Karachi',
            'phone' => '+9221'.$this->faker->numberBetween(1000000, 9999999),
            'cell' => '+9230'.$this->faker->numberBetween(10000000, 99999999),
            'whatsapp' => '+9230'.$this->faker->numberBetween(10000000, 99999999),
            'alternate_no' => null,
            'other_chamber_memberships' => null,
            'active_until' => $this->faker->dateTimeBetween('+3 months', '+2 years')->format('Y-m-d'),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'active_until' => $this->faker->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function neverActivated(): self
    {
        return $this->state(fn () => ['active_until' => null]);
    }
}
