# MiniFlow

**전자결재/요청 워크플로우 미니 SaaS**

> 휴가신청, 지출결의, 권한요청 등 사내 워크플로우를 간편하게 처리하는 경량 결재 시스템

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 주요 기능

| 기능 | 설명 |
|------|------|
| **요청서 생성** | 휴가신청, 지출결의 등 다양한 양식으로 요청 작성 |
| **다단계 결재** | 1차 → 2차 → 최종 승인까지 유연한 결재선 구성 |
| **상태 관리** | DRAFT → SUBMITTED → APPROVED/REJECTED 상태 전이 |
| **RBAC 권한** | 사용자/결재자/관리자 역할 기반 접근 제어 |
| **감사 로그** | 모든 결재 이력 추적 (누가, 언제, 무엇을) |
| **첨부파일** | 증빙자료, 참고문서 첨부 지원 |

---

## 빠른 시작

### Docker로 실행 (권장)

```bash
# 1. 저장소 클론
git clone https://github.com/your-username/miniflow.git
cd miniflow

# 2. Docker 실행
docker compose up -d

# 3. 마이그레이션 & 시드 데이터
docker compose exec app php artisan migrate --seed

# 4. 접속
open http://localhost:8080
```

### 데모 계정

| 역할 | 이메일 | 비밀번호 |
|------|--------|----------|
| 관리자 | `admin@miniflow.test` | `password` |
| 결재자 (팀장) | `manager@miniflow.test` | `password` |
| 결재자 (부서장) | `director@miniflow.test` | `password` |
| 일반 사용자 | `user@miniflow.test` | `password` |

---

## 아키텍처

### 시스템 구성도

```
┌─────────────────────────────────────────────────────────────┐
│                        MiniFlow                              │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Web UI    │  │  REST API   │  │   Queue Workers     │  │
│  │  (Blade)    │  │  (JSON)     │  │  (알림/PDF생성)      │  │
│  └──────┬──────┘  └──────┬──────┘  └──────────┬──────────┘  │
│         │                │                     │             │
│  ┌──────┴────────────────┴─────────────────────┴──────────┐  │
│  │                    Laravel Application                  │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌─────────┐  │  │
│  │  │  Request  │ │  Approval │ │   User    │ │  Audit  │  │  │
│  │  │  Domain   │ │  Domain   │ │  Domain   │ │  Domain │  │  │
│  │  └───────────┘ └───────────┘ └───────────┘ └─────────┘  │  │
│  └─────────────────────────────────────────────────────────┘  │
│         │                │                     │             │
│  ┌──────┴────────────────┴─────────────────────┴──────────┐  │
│  │                    MySQL / Redis                        │  │
│  └─────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 상태 전이 다이어그램

```
                    ┌──────────────────────────────────────┐
                    │                                      │
                    ▼                                      │
┌────────┐    ┌───────────┐    ┌──────────┐    ┌─────────┐ │
│ DRAFT  │───▶│ SUBMITTED │───▶│ PENDING  │───▶│APPROVED │─┘
└────────┘    └───────────┘    └──────────┘    └─────────┘
    │              │                │
    │              │                │         ┌──────────┐
    │              └────────────────┴────────▶│ REJECTED │
    │                                         └──────────┘
    ▼
┌──────────┐
│ CANCELED │
└──────────┘
```

---

## 핵심 설계 포인트

### 1. 상태 전이 규칙 (State Machine)
허용된 상태 전이만 가능하도록 검증

### 2. 동시성 제어 (중복 승인 방지)
비관적 잠금(`lockForUpdate`)으로 동시 승인 방지

### 3. 감사 로그 (Audit Trail)
모든 주요 액션 자동 기록 (spatie/laravel-activitylog)

### 4. 정책 기반 접근 제어 (Policy)
Laravel Policy로 세밀한 권한 검증

---

## 기술 스택

- **Backend**: PHP 8.2+, Laravel 12
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Auth**: Laravel Sanctum
- **RBAC**: spatie/laravel-permission
- **Audit**: spatie/laravel-activitylog
- **Container**: Docker Compose

---

## 문서

- [🚀 배포 가이드](docs/DEPLOYMENT.md) - **Docker, AWS, 일반 서버 배포 방법**
- [📖 API 명세서](docs/API.md) - REST API 엔드포인트 명세
- [🗄️ 데이터베이스 설계](docs/DATABASE.md) - ERD 및 테이블 설계
- [🖥️ 화면 목록](docs/SCREENS.md) - UI 화면 구성
- [⚙️ 구현 가이드](docs/IMPLEMENTATION.md) - 개발 가이드라인

### API 문서 (Swagger UI)

```bash
# 서버 실행 후
open http://localhost:8080/docs
```

---

## 테스트

```bash
docker compose exec app php artisan test
```

---

## 라이선스

MIT License
