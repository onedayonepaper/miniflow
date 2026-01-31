<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalStepApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ApprovalRequest $request,
        public ApprovalStep $step
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $stepInfo = "({$this->step->step_order}단계)";
        return new Envelope(
            subject: "[MiniFlow] 승인됨 {$stepInfo}: {$this->request->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.approval.approved',
            with: [
                'request' => $this->request,
                'step' => $this->step,
                'approver' => $this->step->approver,
                'requester' => $this->request->requester,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
