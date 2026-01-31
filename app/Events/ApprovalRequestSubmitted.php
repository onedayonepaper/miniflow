<?php

namespace App\Events;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ApprovalRequest $request,
        public ApprovalStep $firstStep
    ) {}
}
