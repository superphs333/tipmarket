# 팁 관리

## 목적

생활 문제 해결 팁을 작성하고 분류하기 위한 도메인 기반을 만든다. 현재는 팁 모델, 카테고리, 태그, draft 저장 흐름을 중심으로 구현되어 있다.

## 현재 상태

| 항목 | 내용 |
| --- | --- |
| 구분 | 직접 구현 |
| 상태 | 구현 중 |
| 주요 모델 | `Tip`, `Category`, `Tag` |
| 현재 저장 상태 | AI 생성 팁을 draft 상태로 저장 |

## 사용자 흐름

1. 운영자 또는 작성자가 팁 생성 흐름에 진입한다.
2. 제목, 본문, 카테고리, 태그 정보가 `TipDraftData` 형태로 정리된다.
3. `CreateTip` Action이 트랜잭션 안에서 팁을 draft 상태로 저장한다.
4. 기존 태그와 신규 태그 후보를 실제 태그 id 목록으로 변환한다.
5. 저장된 팁과 태그를 pivot 테이블로 연결한다.

## 구현 구조

| 영역 | 파일 | 역할 |
| --- | --- | --- |
| 팁 모델 | `src/app/Models/Tip.php` | 팁 상태, 작성자, 카테고리, 태그 관계 정의 |
| 카테고리 모델 | `src/app/Models/Category.php` | 팁 분류 기준 |
| 태그 모델 | `src/app/Models/Tag.php` | 태그 이름, 활성 상태, 사용량 관리 |
| 초안 데이터 | `src/app/Data/Tips/TipDraftData.php` | 저장 전 팁 데이터를 전달하는 DTO |
| 저장 Action | `src/app/Actions/Tips/CreateTip.php` | draft 팁 저장과 태그 연결 |
| 태그 처리 | `src/app/Actions/Tags/FindOrCreateTags.php` | 기존/신규 태그를 실제 id 목록으로 변환 |
| 권한 | `src/app/Policies/TipPolicy.php` | 팁 관리 메뉴 접근 제어 |
| 콘솔 화면 | `src/resources/views/console/tips/index.blade.php` | 팁 관리 화면 |

## 구현 포인트

- 팁 저장을 Controller나 Livewire 컴포넌트에 두지 않고 `CreateTip` Action으로 분리했다.
- 저장 흐름은 트랜잭션으로 묶어 팁과 태그 연결의 일관성을 유지한다.
- `TipDraftData`를 사용해 AI 생성, 직접 작성, import 같은 여러 입력 출처를 같은 저장 Action으로 연결할 수 있게 했다.
- 팁 상태는 `draft`, `published`, `hidden`, `archived`로 확장 가능하게 정의했다.

## 검증

```bash
docker compose exec -T app php artisan test tests/Feature/Console/TipManagementTest.php
```

## 남은 작업

- 직접 작성 폼과 수정/삭제 기능 연결
- 공개/숨김/보관 상태 전환 기능
- 목록 pagination, 검색, 필터링
- 사용자 공개 화면과 상세 화면
