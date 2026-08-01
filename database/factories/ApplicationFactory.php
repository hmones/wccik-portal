<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
            'authorized_representative_name' => fake()->name(),
            'cnic' => fake()->numerify('#####-#######-#'),
            'company_name' => fake()->company(),
            'company_classification' => fake()->randomElement(CompanyClassification::cases())->value,
            'address' => fake()->streetAddress(),
            'district' => fake()->city(),
            'cell' => fake()->numerify('03##-#######'),
            'whatsapp' => fake()->optional()->numerify('03##-#######'),
            'phone' => fake()->optional()->numerify('021-#######'),
            'email' => fake()->unique()->safeEmail(),
            'has_ntn' => false,
            'ntn_number' => null,
            'submitted_at' => now(),
        ];
    }

    public function withNtn(): static
    {
        return $this->state(fn () => [
            'has_ntn' => true,
            'ntn_number' => fake()->numerify('#######'),
        ]);
    }

    public function renewal(): static
    {
        return $this->state(fn () => [
            'type' => ApplicationType::Renewal,
            'existing_membership_number' => fake()->numerify('WCCIK-####'),
        ]);
    }
}
