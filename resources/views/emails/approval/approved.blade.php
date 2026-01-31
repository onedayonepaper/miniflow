<x-mail::message>
# 결재 승인 알림

귀하의 결재 요청이 **승인**되었습니다.

## 요청 정보

| 항목 | 내용 |
|:-----|:-----|
| 제목 | {{ $request->title }} |
| 결재자 | {{ $approver->name }} |
| 결재단계 | {{ $step->step_order }}단계 |
| 승인일시 | {{ $step->processed_at?->format('Y-m-d H:i') }} |

@if($step->comment)
### 결재 의견
{{ $step->comment }}
@endif

<x-mail::button :url="config('app.frontend_url', config('app.url')) . '/requests/' . $request->id">
상세보기
</x-mail::button>

감사합니다,<br>
{{ config('app.name') }}
</x-mail::message>
