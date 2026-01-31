<x-mail::message>
# 최종 승인 완료

귀하의 결재 요청이 **최종 승인**되었습니다.

## 요청 정보

| 항목 | 내용 |
|:-----|:-----|
| 제목 | {{ $request->title }} |
| 완료일시 | {{ $request->completed_at?->format('Y-m-d H:i') }} |

## 결재 이력

| 단계 | 결재자 | 상태 | 처리일시 |
|:-----|:-------|:-----|:---------|
@foreach($steps as $step)
| {{ $step->step_order }} | {{ $step->approver->name }} | {{ $step->status_label }} | {{ $step->processed_at?->format('Y-m-d H:i') ?? '-' }} |
@endforeach

<x-mail::button :url="config('app.frontend_url', config('app.url')) . '/requests/' . $request->id">
상세보기
</x-mail::button>

감사합니다,<br>
{{ config('app.name') }}
</x-mail::message>
