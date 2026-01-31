<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'position' => $this->position,
            'role' => $this->role,
            'full_title' => $this->full_title,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'pending_approvals_count' => $this->when(
                $this->relationLoaded('approvalSteps'),
                fn () => $this->pending_approvals_count
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
