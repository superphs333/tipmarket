# 계정 설정 커스터마이징

## 목적

Laravel starter kit 기반 계정 화면 중 TipMarket 요구에 맞게 직접 수정한 부분만 정리한다.

회원가입, 로그인, 비밀번호 재설정 같은 기본 인증 기능은 starter kit 기반 기능이므로 이 문서의 대표 구현으로 다루지 않는다.

## 현재 상태

| 항목 | 내용 |
| --- | --- |
| 구분 | 확장/수정 |
| 상태 | 일부 구현 |
| 주요 화면 | 프로필 설정, 화면 설정, 보안 설정 |
| 주요 확장 | locale, 프로필 이미지, 보안 UI 조정 |

## 구현 범위

| 영역 | 파일 | 역할 |
| --- | --- | --- |
| 설정 라우트 | `src/routes/settings.php` | 프로필, 화면 설정, 보안 설정 라우트 구성 |
| 프로필 화면 | `src/resources/views/pages/settings/⚡profile.blade.php` | 사용자 프로필 설정 UI |
| 보안 화면 | `src/resources/views/pages/settings/⚡security.blade.php` | 보안 설정 UI |
| 2FA 모달 | `src/resources/views/pages/settings/⚡two-factor-setup-modal.blade.php` | 2FA 설정 모달 |
| 사용자 모델 | `src/app/Models/User.php` | locale, 역할, 프로필 이미지 관련 속성/관계 |
| locale 컬럼 | `src/database/migrations/2026_06_19_062144_add_locale_to_users_table.php` | 사용자 언어 설정 저장 |
| locale 미들웨어 | `src/app/Http/Middleware/SetLocale.php` | 사용자 locale을 요청에 반영 |

## 구현 포인트

- starter kit의 기본 인증 기능은 유지하되, TipMarket에 필요한 설정 항목만 확장한다.
- 사용자 언어 설정은 `users.locale`로 저장하고 요청 처리 중 locale에 반영한다.
- 프로필 이미지 흐름은 공통 미디어 저장 계층을 사용해 파일 저장과 DB 메타데이터 관리를 분리한다.
- 보안 설정 UI는 2FA 설정 완료 후 모달 닫기와 화면 갱신처럼 사용 흐름을 다듬는 방향으로 조정한다.

## 검증

```bash
docker compose exec -T app php artisan test tests/Feature/Settings/ProfileUpdateTest.php
docker compose exec -T app php artisan test tests/Feature/Settings/SecurityTest.php
docker compose exec -T app php artisan test tests/Feature/Localization/SetLocaleTest.php
```

## 남은 작업

- starter kit 원본 대비 직접 수정한 UI/동작 차이 정리
- 프로필 이미지 정책과 사용자 공개 프로필 연결
- 계정 설정 화면 스크린샷 추가
