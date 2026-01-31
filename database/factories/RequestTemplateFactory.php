<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RequestTemplate>
 */
class RequestTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['휴가신청서', '출장신청서', '구매요청서', '지출결의서']),
            'type' => fake()->randomElement(['leave', 'business_trip', 'purchase', 'expense']),
            'description' => fake()->sentence(),
            'schema' => [
                'fields' => [
                    [
                        'name' => 'reason',
                        'type' => 'text',
                        'label' => '사유',
                        'required' => true,
                    ],
                    [
                        'name' => 'start_date',
                        'type' => 'date',
                        'label' => '시작일',
                        'required' => true,
                    ],
                    [
                        'name' => 'end_date',
                        'type' => 'date',
                        'label' => '종료일',
                        'required' => true,
                    ],
                ],
            ],
            'default_approval_line' => [
                'steps' => [
                    ['type' => 'approve', 'role' => 'team_leader'],
                    ['type' => 'approve', 'role' => 'department_head'],
                ],
            ],
            'is_active' => true,
        ];
    }

    /**
     * Create a leave request template.
     */
    public function leave(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '휴가신청서',
            'type' => 'leave',
        ]);
    }

    /**
     * Create a purchase request template.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '구매요청서',
            'type' => 'purchase',
        ]);
    }

    /**
     * Mark as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Associate with a creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
