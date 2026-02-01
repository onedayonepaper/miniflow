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
            'name' => fake()->randomElement(['일반 신청서', '간편 신청서', '업무 요청서', '승인 요청서']),
            'type' => fake()->randomElement(['general', 'simple', 'request', 'approval']),
            'description' => fake()->sentence(),
            'schema' => [
                'fields' => [
                    [
                        'name' => 'title',
                        'type' => 'text',
                        'label' => '제목',
                        'required' => true,
                    ],
                    [
                        'name' => 'content',
                        'type' => 'textarea',
                        'label' => '내용',
                        'required' => true,
                    ],
                ],
            ],
            'default_approval_line' => [
                'steps' => [
                    ['type' => 'approver', 'label' => '1차 승인'],
                    ['type' => 'approver', 'label' => '최종 승인'],
                ],
            ],
            'is_active' => true,
        ];
    }

    /**
     * Create a general request template.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '일반 신청서',
            'type' => 'general',
            'description' => '범용 신청 양식',
        ]);
    }

    /**
     * Create a simple request template.
     */
    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => '간편 신청서',
            'type' => 'simple',
            'description' => '간단한 요청용 양식',
            'schema' => [
                'fields' => [
                    [
                        'name' => 'content',
                        'type' => 'textarea',
                        'label' => '요청 내용',
                        'required' => true,
                    ],
                ],
            ],
            'default_approval_line' => [
                'steps' => [
                    ['type' => 'approver', 'label' => '승인'],
                ],
            ],
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
