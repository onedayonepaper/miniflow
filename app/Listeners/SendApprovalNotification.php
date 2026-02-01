<?php

namespace App\Listeners;

use App\Events\ApprovalRequestSubmitted;
use App\Events\ApprovalStepProcessed;
use App\Mail\ApprovalRequestCompleted;
use App\Mail\ApprovalRequestSubmitted as ApprovalRequestSubmittedMail;
use App\Mail\ApprovalStepApproved;
use App\Mail\ApprovalStepRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendApprovalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the ApprovalRequestSubmitted event.
     */
    public function handleSubmitted(ApprovalRequestSubmitted $event): void
    {
        $approver = $event->firstStep->approver;

        if ($approver && $approver->email) {
            Mail::to($approver->email)->send(
                new ApprovalRequestSubmittedMail($event->request, $event->firstStep)
            );
        }
    }

    /**
     * Handle the ApprovalStepProcessed event.
     */
    public function handleProcessed(ApprovalStepProcessed $event): void
    {
        $request = $event->step->request;
        $requester = $request->requester;

        if ($event->isRejected()) {
            // 반려 알림 → 신청자에게
            if ($requester && $requester->email) {
                Mail::to($requester->email)->send(
                    new ApprovalStepRejected($request, $event->step)
                );
            }
        } elseif ($event->isFinalApproval()) {
            // 최종 승인 알림 → 신청자에게
            if ($requester && $requester->email) {
                Mail::to($requester->email)->send(
                    new ApprovalRequestCompleted($request)
                );
            }
        } else {
            // 중간 승인 알림
            // 1. 신청자에게 진행 상황 알림
            if ($requester && $requester->email) {
                Mail::to($requester->email)->send(
                    new ApprovalStepApproved($request, $event->step)
                );
            }

            // 2. 다음 승인자에게 승인 요청 알림
            if ($event->nextStep) {
                $nextApprover = $event->nextStep->approver;
                if ($nextApprover && $nextApprover->email) {
                    Mail::to($nextApprover->email)->send(
                        new ApprovalRequestSubmittedMail($request, $event->nextStep)
                    );
                }
            }
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            ApprovalRequestSubmitted::class => 'handleSubmitted',
            ApprovalStepProcessed::class => 'handleProcessed',
        ];
    }
}
