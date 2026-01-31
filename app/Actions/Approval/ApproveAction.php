<?php

namespace App\Actions\Approval;

use App\Events\ApprovalStepProcessed;
use App\Exceptions\ApiException;
use App\Models\ApprovalStep;
use Illuminate\Support\Facades\DB;

class ApproveAction
{
    public function execute(ApprovalStep $step, ?string $comment = null): ApprovalStep
    {
        if (!$step->canProcess()) {
            throw ApiException::conflict('결재할 수 없는 상태입니다.');
        }

        return DB::transaction(function () use ($step, $comment) {
            $request = $step->request;

            // 현재 단계 승인 처리
            $step->update([
                'status' => 'approved',
                'comment' => $comment,
                'processed_at' => now(),
            ]);

            // 다음 단계 확인
            $nextStep = $request->steps()
                ->where('step_order', $step->step_order + 1)
                ->first();

            if ($nextStep) {
                // 다음 결재자 활성화
                $nextStep->update(['status' => 'pending']);

                $request->update([
                    'current_step' => $nextStep->step_order,
                ]);
            } else {
                // 마지막 단계 - 최종 승인
                $request->update([
                    'status' => 'approved',
                    'completed_at' => now(),
                ]);
            }

            $freshStep = $step->fresh(['approver', 'request.steps.approver']);

            // 이벤트 발행 (승인 알림)
            event(new ApprovalStepProcessed(
                $freshStep,
                'approved',
                $nextStep?->fresh('approver')
            ));

            return $freshStep;
        });
    }
}
