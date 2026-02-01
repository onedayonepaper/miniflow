<?php

namespace App\Actions\Request;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use Illuminate\Support\Facades\DB;

class UpdateRequestAction
{
    public function execute(ApprovalRequest $request, array $data): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $data) {
            // 승인선 업데이트
            if (isset($data['approval_line'])) {
                $approvalLine = $data['approval_line'];
                unset($data['approval_line']);

                // 기존 승인 단계 삭제
                $request->steps()->delete();

                // 새 승인 단계 생성
                foreach ($approvalLine as $index => $step) {
                    ApprovalStep::create([
                        'request_id' => $request->id,
                        'approver_id' => $step['approver_id'],
                        'step_order' => $index + 1,
                        'type' => $step['type'] ?? 'approve',
                        'status' => 'waiting',
                    ]);
                }

                $data['total_steps'] = count($approvalLine);
            }

            // 요청서 업데이트
            $request->update($data);

            return $request->fresh(['steps.approver', 'template', 'requester']);
        });
    }
}
