<?php

namespace App\Actions\Request;

use App\Events\ApprovalRequestSubmitted;
use App\Exceptions\ApiException;
use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\DB;

class SubmitRequestAction
{
    public function execute(ApprovalRequest $request): ApprovalRequest
    {
        if (!$request->canSubmit()) {
            throw ApiException::conflict('제출할 수 없는 상태입니다. 현재 상태: ' . $request->status_label);
        }

        if ($request->steps()->count() === 0) {
            throw ApiException::businessError('결재선이 지정되지 않았습니다.');
        }

        return DB::transaction(function () use ($request) {
            // 첫 번째 결재 단계 활성화
            $firstStep = $request->steps()->where('step_order', 1)->first();
            $firstStep->update(['status' => 'pending']);

            // 요청서 상태 업데이트
            $request->update([
                'status' => 'pending',
                'current_step' => 1,
                'submitted_at' => now(),
            ]);

            $freshRequest = $request->fresh(['steps.approver', 'template', 'requester']);

            // 이벤트 발행 (결재자에게 알림)
            event(new ApprovalRequestSubmitted($freshRequest, $firstStep->fresh('approver')));

            return $freshRequest;
        });
    }
}
