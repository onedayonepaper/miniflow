<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalStep>
 */
class ApprovalStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'step_order' => 1,
            'type' => 'approve',
            'status' => 'waiting',
        ];
    }

    /**
     * Associate with a request.
     */
    public function forRequest(ApprovalRequest $request): static
    {
        return $this->state(fn (array $attributes) => [
            'request_id' => $request->id,
        ]);
    }

    /**
     * Associate with an approver.
     */
    public function byApprover(User $approver): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_id' => $approver->id,
        ]);
    }

    /**
     * Set step order.
     */
    public function stepOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'step_order' => $order,
        ]);
    }

    /**
     * Set status to waiting.
     */
    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
        ]);
    }

    /**
     * Set status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Set status to approved.
     */
    public function approved(?string $comment = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'comment' => $comment ?? fake()->sentence(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Set status to rejected.
     */
    public function rejected(string $comment = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'comment' => $comment ?? fake()->sentence(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Set type to review.
     */
    public function review(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'review',
        ]);
    }
}
