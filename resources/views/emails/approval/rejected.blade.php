<x-mail::message>
# 결재 반려 알림

귀하의 결재 요청이 **반려**되었습니다.

## 요청 정보

| 항목 | 내용 |
|:-----|:-----|
| 제목 | {{ $request->title }} |
| 결재자 | {{ $approver->name }} |
| 결재단계 | {{ $step->step_order }}단계 |
| 반려일시 | {{ $step->processed_at?->format('Y-m-d H:i') }} |

### 반려 사유
{{ $rejectReason }}

<x-mail::button :url="config('app.frontend_url', config('app.url')) . '/requests/' . $request->id">
상세보기
</x-mail::button>

감사합니다,<br>
{{ config('app.name') }}
</x-mail::message>
