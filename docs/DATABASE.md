# MiniFlow 데이터베이스 설계

## ERD (Entity Relationship Diagram)

```
┌─────────────────┐       ┌──────────────────┐       ┌─────────────────┐
│      users      │       │  approval_requests│       │ request_templates│
├─────────────────┤       ├──────────────────┤       ├─────────────────┤
│ id              │◀──────│ requester_id     │       │ id              │
│ name            │       │ template_id      │──────▶│ name            │
│ email           │       │ title            │       │ type            │
│ password        │       │ content (JSON)   │       │ schema (JSON)   │
│ department_id   │───┐   │ status           │       │ approval_line   │
│ position        │   │   │ submitted_at     │       │ is_active       │
│ role            │   │   │ completed_at     │       └─────────────────┘
└─────────────────┘   │   └──────────────────┘
        │             │            │
        │             │            │
        ▼             │            ▼
┌─────────────────┐   │   ┌──────────────────┐
│   departments   │   │   │  approval_steps  │
├─────────────────┤   │   ├──────────────────┤
│ id              │◀──┘   │ id               │
│ name            │       │ request_id       │◀──┐
│ parent_id       │───┐   │ approver_id      │   │
└─────────────────┘   │   │ step_order       │   │
        ▲             │   │ type             │   │
        └─────────────┘   │ status           │   │
                          │ comment          │   │
                          │ processed_at     │   │
                          └──────────────────┘   │
                                                 │
┌─────────────────┐       ┌──────────────────┐   │
│   attachments   │       │   audit_logs     │   │
├─────────────────┤       ├──────────────────┤   │
│ id              │       │ id               │   │
│ request_id      │───────│ user_id          │   │
│ filename        │       │ action           │   │
│ original_name   │       │ target_type      │   │
│ mime_type       │       │ target_id        │───┘
│ size            │       │ changes (JSON)   │
│ path            │       │ ip_address       │
└─────────────────┘       │ user_agent       │
                          │ created_at       │
                          └──────────────────┘
```

---

## 테이블 상세 설계

### 1. users (사용자)

```sql
CREATE TABLE users (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    department_id   BIGINT UNSIGNED NULL,
    position        VARCHAR(50) NULL COMMENT '직책: 사원, 대리, 과장, 팀장, 부서장',
    role            ENUM('user', 'approver', 'admin') DEFAULT 'user',
    email_verified_at TIMESTAMP NULL,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    INDEX idx_department (department_id),
    INDEX idx_role (role),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT | PK |
| name | VARCHAR(100) | 이름 |
| email | VARCHAR(255) | 이메일 (로그인 ID) |
| password | VARCHAR(255) | 비밀번호 (bcrypt) |
| department_id | BIGINT | 소속 부서 FK |
| position | VARCHAR(50) | 직책 |
| role | ENUM | 역할: user/approver/admin |

---

### 2. departments (부서)

```sql
CREATE TABLE departments (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    code            VARCHAR(20) NOT NULL UNIQUE,
    parent_id       BIGINT UNSIGNED NULL COMMENT '상위 부서',
    manager_id      BIGINT UNSIGNED NULL COMMENT '부서장',
    sort_order      INT DEFAULT 0,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_parent (parent_id),
    INDEX idx_code (code),
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT | PK |
| name | VARCHAR(100) | 부서명 |
| code | VARCHAR(20) | 부서 코드 (예: DEV, HR) |
| parent_id | BIGINT | 상위 부서 FK (계층 구조) |
| manager_id | BIGINT | 부서장 FK |

---

### 3. request_templates (요청서 양식)

```sql
CREATE TABLE request_templates (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    type            VARCHAR(50) NOT NULL COMMENT 'leave, expense, account, etc.',
    description     TEXT NULL,
    schema          JSON NOT NULL COMMENT '폼 필드 정의',
    default_approval_line JSON NULL COMMENT '기본 결재선 설정',
    is_active       BOOLEAN DEFAULT TRUE,
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (type),
    INDEX idx_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**schema 예시 (휴가신청):**
```json
{
  "fields": [
    {
      "name": "leave_type",
      "label": "휴가 종류",
      "type": "select",
      "options": ["연차", "반차(오전)", "반차(오후)", "병가", "경조사"],
      "required": true
    },
    {
      "name": "start_date",
      "label": "시작일",
      "type": "date",
      "required": true
    },
    {
      "name": "end_date",
      "label": "종료일",
      "type": "date",
      "required": true
    },
    {
      "name": "reason",
      "label": "사유",
      "type": "textarea",
      "required": true,
      "maxLength": 500
    }
  ]
}
```

**default_approval_line 예시:**
```json
{
  "steps": [
    {
      "step": 1,
      "type": "team_leader",
      "label": "팀장 승인"
    },
    {
      "step": 2,
      "type": "department_head",
      "label": "부서장 승인"
    }
  ]
}
```

---

### 4. approval_requests (결재 요청)

```sql
CREATE TABLE approval_requests (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    template_id     BIGINT UNSIGNED NOT NULL,
    requester_id    BIGINT UNSIGNED NOT NULL,
    title           VARCHAR(255) NOT NULL,
    content         JSON NOT NULL COMMENT '양식에 맞는 입력 데이터',
    status          ENUM('draft', 'submitted', 'pending', 'approved', 'rejected', 'canceled')
                    DEFAULT 'draft',
    current_step    INT UNSIGNED DEFAULT 0 COMMENT '현재 결재 단계',
    total_steps     INT UNSIGNED DEFAULT 0 COMMENT '총 결재 단계 수',
    urgency         ENUM('normal', 'urgent', 'critical') DEFAULT 'normal',
    submitted_at    TIMESTAMP NULL,
    completed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    INDEX idx_requester (requester_id),
    INDEX idx_status (status),
    INDEX idx_template (template_id),
    INDEX idx_submitted (submitted_at),
    FOREIGN KEY (template_id) REFERENCES request_templates(id),
    FOREIGN KEY (requester_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT | PK |
| template_id | BIGINT | 사용된 양식 FK |
| requester_id | BIGINT | 신청자 FK |
| title | VARCHAR(255) | 요청 제목 |
| content | JSON | 양식 데이터 |
| status | ENUM | 상태 |
| current_step | INT | 현재 결재 단계 |
| total_steps | INT | 총 결재 단계 |
| urgency | ENUM | 긴급도 |

**content 예시:**
```json
{
  "leave_type": "연차",
  "start_date": "2025-02-03",
  "end_date": "2025-02-05",
  "reason": "가족 여행"
}
```

---

### 5. approval_steps (결재 단계)

```sql
CREATE TABLE approval_steps (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    request_id      BIGINT UNSIGNED NOT NULL,
    approver_id     BIGINT UNSIGNED NOT NULL,
    step_order      INT UNSIGNED NOT NULL COMMENT '결재 순서 (1, 2, 3...)',
    type            ENUM('approve', 'review', 'notify') DEFAULT 'approve'
                    COMMENT 'approve:승인필요, review:검토, notify:참조',
    status          ENUM('waiting', 'pending', 'approved', 'rejected', 'skipped')
                    DEFAULT 'waiting',
    comment         TEXT NULL COMMENT '결재 의견',
    processed_at    TIMESTAMP NULL,
    due_date        TIMESTAMP NULL COMMENT '결재 기한',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_request (request_id),
    INDEX idx_approver (approver_id),
    INDEX idx_status (status),
    INDEX idx_order (request_id, step_order),
    UNIQUE KEY uk_request_step (request_id, step_order),
    FOREIGN KEY (request_id) REFERENCES approval_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT | PK |
| request_id | BIGINT | 결재 요청 FK |
| approver_id | BIGINT | 결재자 FK |
| step_order | INT | 결재 순서 |
| type | ENUM | 타입: 승인/검토/참조 |
| status | ENUM | 상태: waiting/pending/approved/rejected/skipped |
| comment | TEXT | 결재 의견 |

**status 설명:**
- `waiting`: 이전 단계 대기 중
- `pending`: 현재 결재 대기 중
- `approved`: 승인됨
- `rejected`: 반려됨
- `skipped`: 건너뜀 (참조자)

---

### 6. attachments (첨부파일)

```sql
CREATE TABLE attachments (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    request_id      BIGINT UNSIGNED NOT NULL,
    uploader_id     BIGINT UNSIGNED NOT NULL,
    filename        VARCHAR(255) NOT NULL COMMENT '저장된 파일명 (UUID)',
    original_name   VARCHAR(255) NOT NULL COMMENT '원본 파일명',
    mime_type       VARCHAR(100) NOT NULL,
    size            BIGINT UNSIGNED NOT NULL COMMENT '파일 크기 (bytes)',
    path            VARCHAR(500) NOT NULL COMMENT '저장 경로',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_request (request_id),
    FOREIGN KEY (request_id) REFERENCES approval_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 7. audit_logs (감사 로그)

```sql
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT UNSIGNED NULL COMMENT 'NULL이면 시스템',
    action          VARCHAR(50) NOT NULL COMMENT 'create, update, delete, approve, reject, submit...',
    target_type     VARCHAR(100) NOT NULL COMMENT 'App\\Models\\ApprovalRequest 등',
    target_id       BIGINT UNSIGNED NOT NULL,
    changes         JSON NULL COMMENT '변경 내역 {before: {}, after: {}}',
    metadata        JSON NULL COMMENT '추가 정보',
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(500) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | BIGINT | PK |
| user_id | BIGINT | 수행자 FK (NULL=시스템) |
| action | VARCHAR(50) | 액션: create/update/delete/approve/reject/submit |
| target_type | VARCHAR(100) | 대상 모델 클래스 |
| target_id | BIGINT | 대상 ID |
| changes | JSON | 변경 전/후 데이터 |
| metadata | JSON | 추가 메타데이터 |

**changes 예시:**
```json
{
  "before": {
    "status": "pending"
  },
  "after": {
    "status": "approved"
  }
}
```

---

### 8. notifications (알림)

```sql
CREATE TABLE notifications (
    id              CHAR(36) PRIMARY KEY,
    type            VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id   BIGINT UNSIGNED NOT NULL,
    data            JSON NOT NULL,
    read_at         TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_notifiable (notifiable_type, notifiable_id),
    INDEX idx_read (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 인덱스 전략

### 자주 사용되는 쿼리별 인덱스

```sql
-- 1. 내 결재 대기 목록 (결재자 기준)
-- SELECT * FROM approval_steps WHERE approver_id = ? AND status = 'pending'
INDEX idx_approver_status (approver_id, status)

-- 2. 내 요청 목록 (신청자 기준)
-- SELECT * FROM approval_requests WHERE requester_id = ? ORDER BY created_at DESC
INDEX idx_requester_created (requester_id, created_at DESC)

-- 3. 상태별 요청 조회
-- SELECT * FROM approval_requests WHERE status = ? ORDER BY submitted_at DESC
INDEX idx_status_submitted (status, submitted_at DESC)

-- 4. 감사 로그 조회
-- SELECT * FROM audit_logs WHERE target_type = ? AND target_id = ? ORDER BY created_at DESC
INDEX idx_target_created (target_type, target_id, created_at DESC)
```

---

## 마이그레이션 순서

1. `create_departments_table`
2. `create_users_table`
3. `add_department_foreign_key_to_departments` (manager_id)
4. `create_request_templates_table`
5. `create_approval_requests_table`
6. `create_approval_steps_table`
7. `create_attachments_table`
8. `create_audit_logs_table`
9. `create_notifications_table`

---

## 시드 데이터

### 부서
```
경영지원본부
├── 인사팀
├── 총무팀
└── 재무팀

개발본부
├── 개발1팀
├── 개발2팀
└── QA팀
```

### 양식 템플릿
1. **휴가신청** - 휴가종류, 시작일, 종료일, 사유
2. **지출결의** - 금액, 지출일, 지출항목, 증빙

### 사용자
| 이메일 | 이름 | 부서 | 직책 | 역할 |
|--------|------|------|------|------|
| admin@miniflow.test | 관리자 | 경영지원본부 | 본부장 | admin |
| manager@miniflow.test | 김팀장 | 개발1팀 | 팀장 | approver |
| director@miniflow.test | 박부서장 | 개발본부 | 본부장 | approver |
| user@miniflow.test | 이사원 | 개발1팀 | 사원 | user |
