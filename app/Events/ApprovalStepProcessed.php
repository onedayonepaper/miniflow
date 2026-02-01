<?php

namespace App\Events;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalStepProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param ApprovalStep $step 처리된 승인 단계
     * @param string $action 'approved' 또는 'rejected'
     * @param ApprovalStep|null $nextStep 다음 승인 단계 (있는 경우)
     */
    public function __construct(
        public ApprovalStep $step,
        public string $action,
        public ?ApprovalStep $nextStep = null
    ) {}

    /**
     * 최종 승인 여부 확인
     */
    public function isFinalApproval(): bool
    {
        return $this->action === 'approved' && $this->nextStep === null;
    }

    /**
     * 반려 여부 확인
     */
    public function isRejected(): bool
    {
        return $this->action === 'rejected';
    }
}
