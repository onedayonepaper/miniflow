<?php

namespace App\Jobs;

use App\Mail\ApprovalRequestCompleted;
use App\Mail\ApprovalRequestSubmitted;
use App\Mail\ApprovalStepApproved;
use App\Mail\ApprovalStepRejected;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendApprovalNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param string $type 알림 유형: 'submitted', 'approved', 'rejected', 'completed'
     * @param ApprovalRequest $request 결재 요청서
     * @param ApprovalStep|null $step 결재 단계 (submitted, approved, rejected일 때 필요)
     * @param string|null $recipientEmail 수신자 이메일
     */
    public function __construct(
        public string $type,
        public ApprovalRequest $request,
        public ?ApprovalStep $step = null,
        public ?string $recipientEmail = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->recipientEmail)) {
            return;
        }

        $mailable = match ($this->type) {
            'submitted' => new ApprovalRequestSubmitted($this->request, $this->step),
            'approved' => new ApprovalStepApproved($this->request, $this->step),
            'rejected' => new ApprovalStepRejected($this->request, $this->step),
            'completed' => new ApprovalRequestCompleted($this->request),
            default => null,
        };

        if ($mailable) {
            Mail::to($this->recipientEmail)->send($mailable);
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
