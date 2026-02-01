<?php

namespace App\Http\Requests\Request;

use App\Exceptions\ApiException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('request');

        if (!$request->canEdit()) {
            throw ApiException::conflict('임시저장 상태에서만 수정할 수 있습니다.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'urgency' => ['sometimes', Rule::in(['normal', 'urgent', 'critical'])],
            'approval_line' => ['sometimes', 'array', 'min:1'],
            'approval_line.*.approver_id' => ['required_with:approval_line', 'exists:users,id'],
            'approval_line.*.type' => ['sometimes', Rule::in(['approve', 'review', 'notify'])],
        ];
    }

    public function messages(): array
    {
        return [
            'approval_line.min' => '최소 1명의 승인자가 필요합니다.',
            'approval_line.*.approver_id.required_with' => '승인자를 지정해주세요.',
            'approval_line.*.approver_id.exists' => '존재하지 않는 승인자입니다.',
        ];
    }
}
