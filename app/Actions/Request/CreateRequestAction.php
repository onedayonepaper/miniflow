<?php

namespace App\Actions\Request;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRequestAction
{
    public function execute(array $data, User $requester): ApprovalRequest
    {
        return DB::transaction(function () use ($data, $requester) {
            $approvalLine = $data['approval_line'];
            unset($data['approval_line']);

            // 요청서 생성
            $request = ApprovalRequest::create([
                ...$data,
                'requester_id' => $requester->id,
                'status' => 'draft',
                'current_step' => 0,
                'total_steps' => count($approvalLine),
            ]);

            // 승인 단계 생성
            foreach ($approvalLine as $index => $step) {
                ApprovalStep::create([
                    'request_id' => $request->id,
                    'approver_id' => $step['approver_id'],
                    'step_order' => $index + 1,
                    'type' => $step['type'] ?? 'approve',
                    'status' => 'waiting',
                ]);
            }

            return $request->load(['steps.approver', 'template', 'requester']);
        });
    }
}
