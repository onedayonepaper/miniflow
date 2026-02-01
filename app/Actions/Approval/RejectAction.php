<?php

namespace App\Actions\Approval;

use App\Events\ApprovalStepProcessed;
use App\Exceptions\ApiException;
use App\Models\ApprovalStep;
use Illuminate\Support\Facades\DB;

class RejectAction
{
    public function execute(ApprovalStep $step, string $comment): ApprovalStep
    {
        if (!$step->canProcess()) {
            throw ApiException::conflict('승인할 수 없는 상태입니다.');
        }

        return DB::transaction(function () use ($step, $comment) {
            $request = $step->request;

            // 현재 단계 반려 처리
            $step->update([
                'status' => 'rejected',
                'comment' => $comment,
                'processed_at' => now(),
            ]);

            // 이후 단계 모두 건너뜀 처리
            $request->steps()
                ->where('step_order', '>', $step->step_order)
                ->where('status', 'waiting')
                ->update(['status' => 'skipped']);

            // 요청서 반려 처리
            $request->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            $freshStep = $step->fresh(['approver', 'request.steps.approver']);

            // 이벤트 발행 (반려 알림)
            event(new ApprovalStepProcessed($freshStep, 'rejected'));

            return $freshStep;
        });
    }
}
