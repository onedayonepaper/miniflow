# MiniFlow 구현 가이드

## 구현 로드맵

### Phase 1: 프로젝트 셋업 (Day 1)

```bash
# 1. Laravel 프로젝트 생성
composer create-project laravel/laravel miniflow-app
cd miniflow-app

# 2. 필요한 패키지 설치
composer require laravel/sanctum        # API 인증
composer require spatie/laravel-permission  # RBAC
composer require spatie/laravel-activitylog # 감사 로그
composer require barryvdh/laravel-dompdf   # PDF 생성 (선택)

# 3. 개발 도구
composer require --dev laravel/pint      # 코드 스타일
composer require --dev pestphp/pest      # 테스트
composer require --dev pestphp/pest-plugin-laravel
```

---

### Phase 2: 데이터베이스 마이그레이션 (Day 1-2)

**마이그레이션 생성 순서:**

```bash
php artisan make:migration create_departments_table
php artisan make:migration create_users_table  # 기본 제공, 수정 필요
php artisan make:migration create_request_templates_table
php artisan make:migration create_approval_requests_table
php artisan make:migration create_approval_steps_table
php artisan make:migration create_attachments_table
php artisan make:migration create_audit_logs_table
```

**핵심 마이그레이션 예시:**

```php
// create_approval_requests_table.php
Schema::create('approval_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('template_id')->constrained('request_templates');
    $table->foreignId('requester_id')->constrained('users');
    $table->string('title');
    $table->json('content');
    $table->enum('status', ['draft', 'submitted', 'pending', 'approved', 'rejected', 'canceled'])
          ->default('draft');
    $table->unsignedInteger('current_step')->default(0);
    $table->unsignedInteger('total_steps')->default(0);
    $table->enum('urgency', ['normal', 'urgent', 'critical'])->default('normal');
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['requester_id', 'status']);
    $table->index(['status', 'submitted_at']);
});
```

---

### Phase 3: 모델 및 관계 설정 (Day 2)

**디렉토리 구조:**
```
app/
├── Domain/
│   ├── Request/
│   │   ├── Models/
│   │   │   ├── ApprovalRequest.php
│   │   │   └── RequestTemplate.php
│   │   ├── Actions/
│   │   │   ├── CreateRequest.php
│   │   │   ├── SubmitRequest.php
│   │   │   └── CancelRequest.php
│   │   ├── DTOs/
│   │   │   └── RequestData.php
│   │   └── States/
│   │       ├── RequestState.php
│   │       ├── DraftState.php
│   │       ├── SubmittedState.php
│   │       └── ...
│   ├── Approval/
│   │   ├── Models/
│   │   │   └── ApprovalStep.php
│   │   └── Actions/
│   │       ├── ApproveStep.php
│   │       └── RejectStep.php
│   └── User/
│       └── Models/
│           ├── User.php
│           └── Department.php
```

**핵심 모델 예시:**

```php
// app/Domain/Request/Models/ApprovalRequest.php
class ApprovalRequest extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'template_id', 'requester_id', 'title', 'content',
        'status', 'current_step', 'total_steps', 'urgency',
    ];

    protected $casts = [
        'content' => 'array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relations
    public function template(): BelongsTo
    {
        return $this->belongsTo(RequestTemplate::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'request_id')
                    ->orderBy('step_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'request_id');
    }

    public function currentStep(): HasOne
    {
        return $this->hasOne(ApprovalStep::class, 'request_id')
                    ->where('status', 'pending');
    }

    // State Machine
    public function canSubmit(): bool
    {
        return $this->status === 'draft';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'pending']);
    }

    public function submit(): void
    {
        if (!$this->canSubmit()) {
            throw new InvalidStateTransitionException();
        }

        DB::transaction(function () {
            $this->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // 첫 번째 결재자 활성화
            $this->steps()->where('step_order', 1)->update([
                'status' => 'pending',
            ]);

            $this->update(['status' => 'pending', 'current_step' => 1]);

            // 결재자에게 알림
            $this->currentStep->approver->notify(
                new ApprovalRequestedNotification($this)
            );
        });
    }
}
```

---

### Phase 4: 상태 머신 구현 (Day 2-3)

**상태 전이 규칙:**

```php
// app/Domain/Request/States/RequestState.php
abstract class RequestState
{
    protected ApprovalRequest $request;

    public function __construct(ApprovalRequest $request)
    {
        $this->request = $request;
    }

    abstract public function canTransitionTo(string $state): bool;
    abstract public function allowedTransitions(): array;
}

// app/Domain/Request/States/PendingState.php
class PendingState extends RequestState
{
    public function allowedTransitions(): array
    {
        return ['pending', 'approved', 'rejected'];
    }

    public function canTransitionTo(string $state): bool
    {
        return in_array($state, $this->allowedTransitions());
    }
}
```

---

### Phase 5: 결재 처리 로직 (Day 3-4)

**동시성 제어가 포함된 승인 처리:**

```php
// app/Domain/Approval/Actions/ApproveStep.php
class ApproveStep
{
    public function execute(ApprovalStep $step, string $comment = null): ApprovalStep
    {
        return DB::transaction(function () use ($step, $comment) {
            // 비관적 잠금으로 동시 승인 방지
            $lockedStep = ApprovalStep::where('id', $step->id)
                ->lockForUpdate()
                ->first();

            if ($lockedStep->status !== 'pending') {
                throw new AlreadyProcessedException(
                    '이미 처리된 결재입니다.'
                );
            }

            // 승인 처리
            $lockedStep->update([
                'status' => 'approved',
                'comment' => $comment,
                'processed_at' => now(),
            ]);

            // 다음 단계 처리
            $this->processNextStep($lockedStep);

            // 감사 로그
            activity()
                ->performedOn($lockedStep->request)
                ->causedBy(auth()->user())
                ->withProperties([
                    'step_id' => $lockedStep->id,
                    'action' => 'approve',
                    'comment' => $comment,
                ])
                ->log('approved');

            return $lockedStep->fresh();
        });
    }

    private function processNextStep(ApprovalStep $currentStep): void
    {
        $request = $currentStep->request;
        $nextStep = $request->steps()
            ->where('step_order', $currentStep->step_order + 1)
            ->first();

        if ($nextStep) {
            // 다음 결재자 활성화
            $nextStep->update(['status' => 'pending']);
            $request->update(['current_step' => $nextStep->step_order]);

            // 다음 결재자에게 알림
            $nextStep->approver->notify(
                new ApprovalRequestedNotification($request)
            );
        } else {
            // 최종 승인
            $request->update([
                'status' => 'approved',
                'completed_at' => now(),
            ]);

            // 신청자에게 최종 승인 알림
            $request->requester->notify(
                new RequestApprovedNotification($request)
            );
        }
    }
}
```

---

### Phase 6: API 컨트롤러 (Day 4-5)

```php
// app/Http/Controllers/Api/ApprovalRequestController.php
class ApprovalRequestController extends Controller
{
    public function __construct(
        private CreateRequest $createRequest,
        private SubmitRequest $submitRequest,
    ) {}

    public function index(Request $request)
    {
        $requests = ApprovalRequest::query()
            ->where('requester_id', auth()->id())
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->with(['template:id,name,type', 'steps.approver:id,name'])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return ApprovalRequestResource::collection($requests);
    }

    public function store(StoreRequestRequest $request)
    {
        $data = RequestData::fromRequest($request);
        $approvalRequest = $this->createRequest->execute($data);

        return new ApprovalRequestResource($approvalRequest);
    }

    public function submit(ApprovalRequest $request)
    {
        $this->authorize('submit', $request);

        $this->submitRequest->execute($request);

        return response()->json([
            'message' => '결재가 요청되었습니다.',
            'data' => new ApprovalRequestResource($request->fresh()),
        ]);
    }
}
```

---

### Phase 7: 권한 정책 (Day 5)

```php
// app/Policies/ApprovalRequestPolicy.php
class ApprovalRequestPolicy
{
    public function view(User $user, ApprovalRequest $request): bool
    {
        // 본인 요청, 결재자, 관리자만 조회 가능
        return $request->requester_id === $user->id
            || $request->steps()->where('approver_id', $user->id)->exists()
            || $user->hasRole('admin');
    }

    public function update(User $user, ApprovalRequest $request): bool
    {
        // 본인 요청 + draft 상태에서만 수정 가능
        return $request->requester_id === $user->id
            && $request->status === 'draft';
    }

    public function submit(User $user, ApprovalRequest $request): bool
    {
        return $request->requester_id === $user->id
            && $request->canSubmit();
    }

    public function cancel(User $user, ApprovalRequest $request): bool
    {
        return $request->requester_id === $user->id
            && $request->canCancel();
    }
}

// app/Policies/ApprovalStepPolicy.php
class ApprovalStepPolicy
{
    public function approve(User $user, ApprovalStep $step): bool
    {
        return $step->approver_id === $user->id
            && $step->status === 'pending'
            && $step->request->status === 'pending';
    }

    public function reject(User $user, ApprovalStep $step): bool
    {
        return $this->approve($user, $step);
    }
}
```

---

### Phase 8: 테스트 작성 (Day 6)

```php
// tests/Feature/ApprovalTest.php
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user']);
    $this->manager = User::factory()->create(['role' => 'approver']);
    $this->director = User::factory()->create(['role' => 'approver']);
    $this->template = RequestTemplate::factory()->leave()->create();
});

it('can create and submit a request', function () {
    $this->actingAs($this->user);

    $response = $this->postJson('/api/v1/requests', [
        'template_id' => $this->template->id,
        'title' => '연차 휴가 신청',
        'content' => [
            'leave_type' => '연차',
            'start_date' => '2025-02-03',
            'end_date' => '2025-02-05',
            'reason' => '가족 여행',
        ],
        'approval_line' => [
            ['approver_id' => $this->manager->id, 'type' => 'approve'],
            ['approver_id' => $this->director->id, 'type' => 'approve'],
        ],
    ]);

    $response->assertCreated();
    $requestId = $response->json('data.id');

    // Submit
    $this->postJson("/api/v1/requests/{$requestId}/submit")
        ->assertOk();

    $this->assertDatabaseHas('approval_requests', [
        'id' => $requestId,
        'status' => 'pending',
    ]);
});

it('approves through multi-step approval line', function () {
    // 요청 생성 및 제출
    $request = ApprovalRequest::factory()
        ->for($this->user, 'requester')
        ->for($this->template)
        ->submitted()
        ->create();

    ApprovalStep::factory()->pending()->create([
        'request_id' => $request->id,
        'approver_id' => $this->manager->id,
        'step_order' => 1,
    ]);

    ApprovalStep::factory()->waiting()->create([
        'request_id' => $request->id,
        'approver_id' => $this->director->id,
        'step_order' => 2,
    ]);

    // 1차 승인
    $this->actingAs($this->manager)
        ->postJson("/api/v1/approvals/{$request->steps[0]->id}/approve", [
            'comment' => '승인합니다.',
        ])
        ->assertOk();

    expect($request->fresh())
        ->status->toBe('pending')
        ->current_step->toBe(2);

    // 2차 (최종) 승인
    $this->actingAs($this->director)
        ->postJson("/api/v1/approvals/{$request->steps[1]->id}/approve", [
            'comment' => '최종 승인합니다.',
        ])
        ->assertOk();

    expect($request->fresh())
        ->status->toBe('approved')
        ->completed_at->not->toBeNull();
});

it('prevents concurrent approvals', function () {
    $request = createPendingRequest();
    $step = $request->currentStep;

    // 동시 승인 시도 시뮬레이션
    $this->actingAs($this->manager);

    // 첫 번째 승인
    $response1 = $this->postJson("/api/v1/approvals/{$step->id}/approve");

    // 두 번째 승인 시도 (이미 처리됨)
    $response2 = $this->postJson("/api/v1/approvals/{$step->id}/approve");

    $response1->assertOk();
    $response2->assertStatus(409); // Conflict
});
```

---

### Phase 9: 프론트엔드 (Day 7-10)

**Blade + Alpine.js + Tailwind CSS 구성:**

```bash
npm install -D tailwindcss postcss autoprefixer @tailwindcss/forms
npm install alpinejs
npx tailwindcss init -p
```

**컴포넌트 예시:**

```blade
{{-- resources/views/components/approval-status.blade.php --}}
@props(['status'])

@php
$classes = match($status) {
    'draft' => 'bg-gray-100 text-gray-800',
    'submitted', 'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'canceled' => 'bg-gray-100 text-gray-500',
    default => 'bg-gray-100 text-gray-800',
};

$labels = [
    'draft' => '임시저장',
    'submitted' => '제출됨',
    'pending' => '결재중',
    'approved' => '승인',
    'rejected' => '반려',
    'canceled' => '취소',
];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$classes}"]) }}>
    {{ $labels[$status] ?? $status }}
</span>
```

---

## 체크리스트

### MVP 완료 기준

- [ ] Docker 환경 구동
- [ ] 로그인/로그아웃
- [ ] 요청서 CRUD
- [ ] 요청서 제출
- [ ] 결재함 조회
- [ ] 승인/반려 처리
- [ ] 상태 전이 검증
- [ ] 권한 검증
- [ ] 감사 로그 기록
- [ ] 기본 UI 완성

### 테스트 커버리지 목표

- [ ] 단위 테스트: 80%+
- [ ] 통합 테스트: 주요 플로우 100%
- [ ] E2E 테스트: 핵심 시나리오

---

## 일정 요약

| Phase | 내용 | 예상 기간 |
|-------|------|----------|
| 1 | 프로젝트 셋업 | Day 1 |
| 2 | DB 마이그레이션 | Day 1-2 |
| 3 | 모델 및 관계 | Day 2 |
| 4 | 상태 머신 | Day 2-3 |
| 5 | 결재 로직 | Day 3-4 |
| 6 | API 컨트롤러 | Day 4-5 |
| 7 | 권한 정책 | Day 5 |
| 8 | 테스트 | Day 6 |
| 9 | 프론트엔드 | Day 7-10 |

**총 예상 기간: 10일 (주말 제외 2주)**
