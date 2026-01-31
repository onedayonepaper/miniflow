<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_name' => fake()->word() . '.pdf',
            'file_path' => 'attachments/' . fake()->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1024, 1048576), // 1KB to 1MB
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
     * Associate with an uploader.
     */
    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by' => $user->id,
        ]);
    }
}
