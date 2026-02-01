<?php

namespace App\Actions\Request;

use App\Exceptions\ApiException;
use App\Models\ApprovalRequest;
use Illuminate\Support\Facades\DB;

class CancelRequestAction
{
    public function execute(ApprovalRequest $request): ApprovalRequest
    {
        if (!$request->canCancel()) {
            throw ApiException::conflict('취소할 수 없는 상태입니다. 현재 상태: ' . $request->status_label);
        }

        return DB::transaction(function () use ($request) {
            // 모든 대기 중인 승인 단계 건너뜀 처리
            $request->steps()
                ->whereIn('status', ['waiting', 'pending'])
                ->update(['status' => 'skipped']);

            // 요청서 취소 처리
            $request->update([
                'status' => 'canceled',
                'completed_at' => now(),
            ]);

            return $request->fresh(['steps.approver', 'template', 'requester']);
        });
    }
}
