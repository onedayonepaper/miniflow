# MiniFlow API 명세서

## 개요

- **Base URL**: `/api/v1`
- **인증**: Bearer Token (Laravel Sanctum)
- **응답 형식**: JSON
- **에러 형식**: RFC 7807 Problem Details

---

## 인증 (Authentication)

### POST `/auth/login`
로그인

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "이영희",
      "email": "user@example.com",
      "role": "user",
      "department": {
        "id": 5,
        "name": "일반 멤버"
      }
    },
    "token": "1|abcdef123456...",
    "expires_at": "2025-02-28T23:59:59Z"
  }
}
```

**Response (401):**
```json
{
  "type": "https://example.com/errors/unauthorized",
  "title": "Unauthorized",
  "status": 401,
  "detail": "이메일 또는 비밀번호가 올바르지 않습니다."
}
```

---

### POST `/auth/logout`
로그아웃

**Headers:**
```
Authorization: Bearer {token}
```

**Response (204):** No Content

---

### GET `/auth/me`
현재 사용자 정보

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "이영희",
    "email": "user@example.com",
    "role": "user",
    "department": {
      "id": 5,
      "name": "일반 멤버",
      "manager": {
        "id": 2,
        "name": "김철수"
      }
    },
    "position": "멤버",
    "pending_approvals_count": 0,
    "my_requests_count": 3
  }
}
```

---

## 요청서 (Requests)

### GET `/requests`
내 요청서 목록

**Query Parameters:**
| 파라미터 | 타입 | 설명 | 기본값 |
|----------|------|------|--------|
| status | string | draft,submitted,pending,approved,rejected,canceled | - |
| template_id | integer | 양식 ID | - |
| from_date | date | 시작일 (YYYY-MM-DD) | - |
| to_date | date | 종료일 (YYYY-MM-DD) | - |
| sort | string | created_at,submitted_at,updated_at | created_at |
| order | string | asc,desc | desc |
| per_page | integer | 페이지당 개수 (1-100) | 15 |
| page | integer | 페이지 번호 | 1 |

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": "업무 협조 요청 (2/3~2/5)",
      "template": {
        "id": 1,
        "name": "일반 신청서",
        "type": "leave"
      },
      "status": "pending",
      "current_step": 1,
      "total_steps": 2,
      "urgency": "normal",
      "submitted_at": "2025-01-31T09:00:00Z",
      "created_at": "2025-01-31T08:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  },
  "links": {
    "first": "/api/v1/requests?page=1",
    "last": "/api/v1/requests?page=5",
    "prev": null,
    "next": "/api/v1/requests?page=2"
  }
}
```

---

### POST `/requests`
요청서 생성

**Request:**
```json
{
  "template_id": 1,
  "title": "업무 협조 요청 (2/3~2/5)",
  "content": {
    "content": "프로젝트 협조 요청입니다.",
    "start_date": "2025-02-03",
    "end_date": "2025-02-05",
    "reason": "가족 여행"
  },
  "urgency": "normal",
  "approval_line": [
    { "approver_id": 2, "type": "approve" },
    { "approver_id": 3, "type": "approve" }
  ]
}
```

**Response (201):**
```json
{
  "data": {
    "id": 10,
    "title": "업무 협조 요청 (2/3~2/5)",
    "template": {
      "id": 1,
      "name": "일반 신청서"
    },
    "content": {
      "content": "프로젝트 협조 요청입니다.",
      "start_date": "2025-02-03",
      "end_date": "2025-02-05",
      "reason": "가족 여행"
    },
    "status": "draft",
    "urgency": "normal",
    "approval_steps": [
      {
        "step_order": 1,
        "approver": { "id": 2, "name": "김철수" },
        "type": "approve",
        "status": "waiting"
      },
      {
        "step_order": 2,
        "approver": { "id": 3, "name": "홍길동" },
        "type": "approve",
        "status": "waiting"
      }
    ],
    "created_at": "2025-01-31T10:00:00Z"
  }
}
```

**Validation Errors (422):**
```json
{
  "type": "https://example.com/errors/validation",
  "title": "Validation Error",
  "status": 422,
  "errors": {
    "content.start_date": ["시작일은 필수입니다."],
    "content.end_date": ["종료일은 시작일 이후여야 합니다."]
  }
}
```

---

### GET `/requests/{id}`
요청서 상세

**Response (200):**
```json
{
  "data": {
    "id": 10,
    "title": "업무 협조 요청 (2/3~2/5)",
    "template": {
      "id": 1,
      "name": "일반 신청서",
      "type": "leave"
    },
    "requester": {
      "id": 1,
      "name": "이영희",
      "department": "일반 멤버",
      "position": "멤버"
    },
    "content": {
      "content": "프로젝트 협조 요청입니다.",
      "start_date": "2025-02-03",
      "end_date": "2025-02-05",
      "reason": "가족 여행"
    },
    "status": "pending",
    "current_step": 1,
    "total_steps": 2,
    "urgency": "normal",
    "approval_steps": [
      {
        "id": 1,
        "step_order": 1,
        "approver": { "id": 2, "name": "김철수", "position": "담당자" },
        "type": "approve",
        "status": "pending",
        "comment": null,
        "processed_at": null
      },
      {
        "id": 2,
        "step_order": 2,
        "approver": { "id": 3, "name": "홍길동", "position": "관리자" },
        "type": "approve",
        "status": "waiting",
        "comment": null,
        "processed_at": null
      }
    ],
    "attachments": [
      {
        "id": 1,
        "original_name": "증빙서류.pdf",
        "size": 102400,
        "download_url": "/api/v1/attachments/1/download"
      }
    ],
    "audit_logs": [
      {
        "action": "create",
        "user": "이영희",
        "created_at": "2025-01-31T08:30:00Z"
      },
      {
        "action": "submit",
        "user": "이영희",
        "created_at": "2025-01-31T09:00:00Z"
      }
    ],
    "submitted_at": "2025-01-31T09:00:00Z",
    "created_at": "2025-01-31T08:30:00Z"
  }
}
```

---

### PUT `/requests/{id}`
요청서 수정 (draft 상태에서만)

**Request:**
```json
{
  "title": "업무 협조 요청 (2/3~2/4)",
  "content": {
    "content": "프로젝트 협조 요청입니다.",
    "start_date": "2025-02-03",
    "end_date": "2025-02-04",
    "reason": "가족 여행 (일정 변경)"
  }
}
```

**Response (200):** 수정된 요청서 반환

**Error (403):** 제출된 요청서는 수정 불가
```json
{
  "type": "https://example.com/errors/forbidden",
  "title": "Forbidden",
  "status": 403,
  "detail": "제출된 요청서는 수정할 수 없습니다."
}
```

---

### DELETE `/requests/{id}`
요청서 삭제 (draft 상태에서만)

**Response (204):** No Content

---

### POST `/requests/{id}/submit`
요청서 제출

**Response (200):**
```json
{
  "data": {
    "id": 10,
    "status": "submitted",
    "current_step": 1,
    "submitted_at": "2025-01-31T09:00:00Z",
    "message": "승인가 요청되었습니다. 1차 승인자(김철수)에게 알림이 전송되었습니다."
  }
}
```

---

### POST `/requests/{id}/cancel`
요청서 취소 (본인만, 최종 승인 전까지)

**Request:**
```json
{
  "reason": "일정 변경으로 취소합니다."
}
```

**Response (200):**
```json
{
  "data": {
    "id": 10,
    "status": "canceled",
    "message": "요청이 취소되었습니다."
  }
}
```

---

## 승인 (Approvals)

### GET `/approvals`
승인함 (나에게 온 승인 목록)

**Query Parameters:**
| 파라미터 | 타입 | 설명 | 기본값 |
|----------|------|------|--------|
| status | string | pending,approved,rejected,all | pending |
| urgency | string | normal,urgent,critical | - |
| from_date | date | 시작일 | - |
| to_date | date | 종료일 | - |

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "request": {
        "id": 10,
        "title": "업무 협조 요청 (2/3~2/5)",
        "template_type": "leave",
        "requester": {
          "id": 1,
          "name": "이영희",
          "department": "일반 멤버"
        },
        "urgency": "normal",
        "submitted_at": "2025-01-31T09:00:00Z"
      },
      "step_order": 1,
      "type": "approve",
      "status": "pending",
      "due_date": "2025-02-03T18:00:00Z"
    }
  ],
  "meta": {
    "total": 5,
    "pending_count": 3,
    "urgent_count": 1
  }
}
```

---

### GET `/approvals/{id}`
승인 상세 (= 요청서 상세 + 내 승인 권한 정보)

**Response (200):**
```json
{
  "data": {
    "step": {
      "id": 1,
      "step_order": 1,
      "type": "approve",
      "status": "pending",
      "can_approve": true,
      "can_reject": true
    },
    "request": {
      "id": 10,
      "title": "업무 협조 요청 (2/3~2/5)",
      "content": {
        "content": "프로젝트 협조 요청입니다.",
        "start_date": "2025-02-03",
        "end_date": "2025-02-05",
        "reason": "가족 여행"
      },
      "requester": {
        "id": 1,
        "name": "이영희",
        "department": "일반 멤버"
      },
      "attachments": [],
      "previous_steps": []
    }
  }
}
```

---

### POST `/approvals/{id}/approve`
승인

**Request:**
```json
{
  "comment": "승인합니다."
}
```

**Response (200):**
```json
{
  "data": {
    "step": {
      "id": 1,
      "status": "approved",
      "comment": "승인합니다.",
      "processed_at": "2025-01-31T10:00:00Z"
    },
    "request": {
      "id": 10,
      "status": "pending",
      "current_step": 2,
      "message": "1차 승인이 완료되었습니다. 2차 승인자(홍길동)에게 알림이 전송되었습니다."
    }
  }
}
```

**최종 승인 시 Response:**
```json
{
  "data": {
    "step": {
      "id": 2,
      "status": "approved",
      "processed_at": "2025-01-31T14:00:00Z"
    },
    "request": {
      "id": 10,
      "status": "approved",
      "completed_at": "2025-01-31T14:00:00Z",
      "message": "최종 승인되었습니다."
    }
  }
}
```

---

### POST `/approvals/{id}/reject`
반려

**Request:**
```json
{
  "comment": "사유가 불충분합니다. 상세 내용을 추가해주세요."
}
```

**Response (200):**
```json
{
  "data": {
    "step": {
      "id": 1,
      "status": "rejected",
      "comment": "사유가 불충분합니다. 상세 내용을 추가해주세요.",
      "processed_at": "2025-01-31T10:00:00Z"
    },
    "request": {
      "id": 10,
      "status": "rejected",
      "completed_at": "2025-01-31T10:00:00Z",
      "message": "반려되었습니다. 신청자(이영희)에게 알림이 전송되었습니다."
    }
  }
}
```

---

## 양식 (Templates)

### GET `/templates`
사용 가능한 양식 목록

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "일반 신청서",
      "type": "leave",
      "description": "범용 신청 양식",
      "schema": {
        "fields": [
          {
            "name": "leave_type",
            "label": "제목",
            "type": "select",
            "maxLength": 100,
            "required": true
          }
        ]
      },
      "default_approval_line": {
        "steps": [
          { "step": 1, "type": "team_leader" },
          { "step": 2, "type": "department_head" }
        ]
      }
    },
    {
      "id": 2,
      "name": "간편 신청서",
      "type": "expense",
      "description": "간단한 요청용 양식"
    }
  ]
}
```

---

### GET `/templates/{id}`
양식 상세 (폼 스키마 포함)

---

## 첨부파일 (Attachments)

### POST `/requests/{id}/attachments`
첨부파일 업로드

**Request:** `multipart/form-data`
```
file: [binary]
```

**Response (201):**
```json
{
  "data": {
    "id": 1,
    "original_name": "증빙서류.pdf",
    "mime_type": "application/pdf",
    "size": 102400,
    "download_url": "/api/v1/attachments/1/download"
  }
}
```

---

### GET `/attachments/{id}/download`
첨부파일 다운로드

**Response:** Binary file with appropriate headers

---

### DELETE `/attachments/{id}`
첨부파일 삭제 (draft 상태에서만)

---

## 관리자 (Admin)

### GET `/admin/users`
사용자 목록 (Admin only)

**Query Parameters:**
- `role`: user,approver,admin
- `department_id`: 부서 ID
- `search`: 이름/이메일 검색

---

### GET `/admin/audit-logs`
감사 로그 (Admin only)

**Query Parameters:**
| 파라미터 | 타입 | 설명 |
|----------|------|------|
| user_id | integer | 사용자 필터 |
| action | string | create,update,delete,approve,reject,submit |
| target_type | string | approval_request,approval_step,user |
| target_id | integer | 대상 ID |
| from_date | datetime | 시작일시 |
| to_date | datetime | 종료일시 |

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 2,
        "name": "김철수"
      },
      "action": "approve",
      "target_type": "approval_step",
      "target_id": 1,
      "changes": {
        "before": { "status": "pending" },
        "after": { "status": "approved", "comment": "승인합니다." }
      },
      "ip_address": "192.168.1.100",
      "created_at": "2025-01-31T10:00:00Z"
    }
  ]
}
```

---

### GET `/admin/statistics`
통계 (Admin only)

**Response (200):**
```json
{
  "data": {
    "requests": {
      "total": 150,
      "by_status": {
        "draft": 5,
        "submitted": 10,
        "pending": 20,
        "approved": 100,
        "rejected": 10,
        "canceled": 5
      },
      "by_template": {
        "leave": 80,
        "expense": 70
      }
    },
    "approvals": {
      "avg_processing_time_hours": 4.5,
      "pending_count": 25,
      "overdue_count": 3
    },
    "users": {
      "total": 50,
      "active_this_month": 45
    }
  }
}
```

---

## 에러 응답 형식

모든 에러는 RFC 7807 Problem Details 형식을 따릅니다.

```json
{
  "type": "https://example.com/errors/{error-type}",
  "title": "Error Title",
  "status": 400,
  "detail": "상세 에러 메시지",
  "errors": {
    "field_name": ["검증 에러 메시지"]
  }
}
```

### 에러 코드

| Status | Type | 설명 |
|--------|------|------|
| 400 | bad_request | 잘못된 요청 |
| 401 | unauthorized | 인증 필요 |
| 403 | forbidden | 권한 없음 |
| 404 | not_found | 리소스 없음 |
| 409 | conflict | 상태 충돌 (이미 처리됨 등) |
| 422 | validation | 검증 실패 |
| 429 | rate_limit | 요청 제한 초과 |
| 500 | internal_error | 서버 에러 |

---

## Rate Limiting

- 인증된 사용자: 분당 60회
- 미인증: 분당 10회

**헤더:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
X-RateLimit-Reset: 1706698800
```
