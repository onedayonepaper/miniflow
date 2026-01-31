<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'urgency' => $this->urgency,
            'urgency_label' => $this->urgency_label,
            'current_step' => $this->current_step,
            'total_steps' => $this->total_steps,
            'progress' => $this->progress,
            'template' => new TemplateResource($this->whenLoaded('template')),
            'requester' => new UserResource($this->whenLoaded('requester')),
            'steps' => ApprovalStepResource::collection($this->whenLoaded('steps')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'current_step_info' => new ApprovalStepResource($this->whenLoaded('currentStep')),
            'can_edit' => $this->canEdit(),
            'can_submit' => $this->canSubmit(),
            'can_cancel' => $this->canCancel(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
