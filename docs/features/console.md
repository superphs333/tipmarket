# 역할 기반 콘솔

## 목적

TipMarket의 운영 기능을 일반 사용자 화면과 분리하고, 관리자/운영자 역할에 따라 콘솔 접근 권한을 제한한다.

현재는 콘솔 대시보드와 팁 관리 메뉴의 접근 기반을 구성한 상태다. 세부 관리 기능은 팁 관리, 신고, 사용자 관리 같은 기능이 추가될 때 정책 단위로 확장한다.

## 현재 상태

| 항목 | 내용 |
| --- | --- |
| 구분 | 직접 구현 |
| 상태 | 구현 중 |
| 진입 경로 | `/console`, `/console/tips` |
| 접근 조건 | 로그인, 이메일 인증, 콘솔 접근 역할 |

## 사용자 흐름

1. 인증된 사용자가 콘솔 URL로 접근한다.
2. 라우트 미들웨어가 이메일 인증 여부와 역할을 확인한다.
3. 콘솔 접근 역할이 없으면 접근을 차단한다.
4. 팁 관리 메뉴는 `TipPolicy::viewAny()`로 한 번 더 권한을 좁힌다.

## 구현 구조

| 영역 | 파일 | 역할 |
| --- | --- | --- |
| 라우트 | `src/routes/web.php` | `/console` 라우트 그룹과 팁 관리 메뉴 정의 |
| 역할 모델 | `src/app/Models/Role.php` | 콘솔 접근 역할과 팁 관리 역할 목록 정의 |
| 미들웨어 | `src/app/Http/Middleware/EnsureUserHasRole.php` | 사용자 역할 확인 |
| 대시보드 | `src/app/Http/Controllers/Console/DashboardController.php` | 콘솔 대시보드 표시 |
| 팁 메뉴 | `src/app/Http/Controllers/Console/TipController.php` | 팁 요약 정보 표시 |
| 정책 | `src/app/Policies/TipPolicy.php` | 팁 관리 메뉴 접근 권한 판단 |
| 화면 | `src/resources/views/layouts/console.blade.php` | 콘솔 레이아웃 |

## 구현 포인트

- 콘솔 접근 권한과 팁 관리 권한을 분리했다.
- 라우트 그룹에서 공통 접근 조건을 먼저 검증하고, 메뉴별 권한은 Policy로 좁힌다.
- 역할 이름은 상수로 관리해 문자열 중복과 오타 가능성을 줄였다.

## 검증

```bash
docker compose exec -T app php artisan test tests/Feature/Console/DashboardTest.php
docker compose exec -T app php artisan test tests/Feature/Console/TipManagementTest.php
```

## 남은 작업

- 세부 콘솔 메뉴별 Policy 확장
- 사용자/신고/콘텐츠 관리 화면 추가
- 역할 부여와 회수 관리 화면 추가
