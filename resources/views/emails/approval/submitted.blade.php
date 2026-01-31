<x-mail::message>
# 결재 요청

**{{ $requester->name }}**님이 결재를 요청했습니다.

## 요청 정보

| 항목 | 내용 |
|:-----|:-----|
| 제목 | {{ $request->title }} |
| 신청자 | {{ $requester->name }} ({{ $requester->department?->name ?? '소속 없음' }}) |
| 결재단계 | {{ $step->step_order }}단계 |
| 제출일시 | {{ $request->submitted_at?->format('Y-m-d H:i') }} |

<x-mail::button :url="config('app.frontend_url', config('app.url')) . '/approvals/' . $step->id">
결재하기
</x-mail::button>

감사합니다,<br>
{{ config('app.name') }}
</x-mail::message>
