<?php

namespace App\Http\Requests\Request;

use App\Models\RequestTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'exists:request_templates,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'urgency' => ['sometimes', Rule::in(['normal', 'urgent', 'critical'])],
            'approval_line' => ['required', 'array', 'min:1'],
            'approval_line.*.approver_id' => ['required', 'exists:users,id'],
            'approval_line.*.type' => ['sometimes', Rule::in(['approve', 'review', 'notify'])],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => '양식을 선택해주세요.',
            'template_id.exists' => '존재하지 않는 양식입니다.',
            'title.required' => '제목을 입력해주세요.',
            'content.required' => '내용을 입력해주세요.',
            'approval_line.required' => '승인선을 지정해주세요.',
            'approval_line.min' => '최소 1명의 승인자가 필요합니다.',
            'approval_line.*.approver_id.required' => '승인자를 지정해주세요.',
            'approval_line.*.approver_id.exists' => '존재하지 않는 승인자입니다.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $template = RequestTemplate::find($this->template_id);

            if ($template && !$template->is_active) {
                $validator->errors()->add('template_id', '비활성화된 양식입니다.');
            }
        });
    }
}
