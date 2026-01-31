<?php

namespace Database\Factories;

use App\Models\RequestTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'content' => [
                'reason' => fake()->paragraph(),
                'start_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
                'end_date' => fake()->dateTimeBetween('+1 month', '+2 months')->format('Y-m-d'),
            ],
            'status' => 'draft',
            'current_step' => 0,
            'total_steps' => 2,
            'urgency' => fake()->randomElement(['normal', 'urgent', 'critical']),
        ];
    }

    /**
     * Associate with a template.
     */
    public function forTemplate(RequestTemplate $template): static
    {
        return $this->state(fn (array $attributes) => [
            'template_id' => $template->id,
        ]);
    }

    /**
     * Associate with a requester.
     */
    public function byRequester(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'requester_id' => $user->id,
        ]);
    }

    /**
     * Set status to draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'current_step' => 0,
        ]);
    }

    /**
     * Set status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'current_step' => 1,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Set status to approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now()->subDays(3),
            'completed_at' => now(),
        ]);
    }

    /**
     * Set status to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'submitted_at' => now()->subDays(3),
            'completed_at' => now(),
        ]);
    }

    /**
     * Set urgency to urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'urgency' => 'urgent',
        ]);
    }
}
