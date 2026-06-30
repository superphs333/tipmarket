# 미디어/태그

## 목적

팁과 사용자 프로필에서 사용할 이미지 파일과 태그 데이터를 재사용 가능한 기반 기능으로 분리한다.

미디어는 실제 파일과 DB 메타데이터를 분리하고, 태그는 검색/선택/신규 후보 입력을 공통 컴포넌트로 처리한다.

## 현재 상태

| 항목 | 내용 |
| --- | --- |
| 구분 | 직접 구현 |
| 상태 | 일부 구현 |
| 미디어 연결 | 프로필 이미지 저장/교체/삭제 흐름 연결 |
| 태그 연결 | 태그 선택기와 팁 저장 Action 연결 |

## 구현 구조

| 영역 | 파일 | 역할 |
| --- | --- | --- |
| 미디어 용도 | `src/app/Enums/MediaCollection.php` | 프로필 이미지, 팁 썸네일, 질문 썸네일 구분 |
| 미디어 모델 | `src/app/Models/Media.php` | 파일 메타데이터, owner, 상태 관리 |
| 저장 서비스 | `src/app/Services/Media/MediaStorageService.php` | 파일 저장, DB 레코드 생성, 파일 삭제 |
| 경로 생성 | `src/app/Services/Media/MediaPathGenerator.php` | collection과 owner 기준 저장 경로 생성 |
| 태그 선택기 | `src/app/Livewire/Tags/TagSelector.php` | 태그 검색, 선택, 신규 후보 입력 |
| 태그 검색 | `src/app/Services/Tags/TagSearchService.php` | 활성 태그 검색 |
| 태그 생성 | `src/app/Actions/Tags/FindOrCreateTags.php` | 기존/신규 태그를 저장 가능한 id 목록으로 변환 |

## 구현 포인트

- 업로드 파일은 storage disk에 저장하고, DB에는 관리용 메타데이터만 저장한다.
- owner가 아직 없는 팁/질문 썸네일은 `temporary` 상태로 저장할 수 있게 경로 규칙을 분리했다.
- 태그 선택기는 DB 저장을 직접 하지 않고 입력값을 태그명 배열로 부모에게 전달한다.
- 실제 태그 생성과 연결은 저장 Action에서 처리해 UI 컴포넌트와 도메인 저장 책임을 분리했다.

## 상세 문서

- [미디어 저장 구조](../media-storage.md)
- [태그 선택기 문서](../templates/tag-selector.md)

## 검증

```bash
docker compose exec -T app php artisan test tests/Feature/Tags/TagSearchTest.php
docker compose exec -T app php artisan test tests/Feature/Settings/ProfileUpdateTest.php
```

## 남은 작업

- 팁 썸네일 업로드 UI 연결
- 임시 파일 attach/cleanup 흐름
- 비공개 파일 접근 제어
- 태그 사용량 갱신 정책 정리
