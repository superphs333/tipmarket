# AI 팁 생성

## 목적

운영자가 콘솔에서 주제, 카테고리, 태그 조건을 입력하면 AI가 팁 초안을 생성하고, 생성된 결과를 draft 팁으로 저장한다.

## 현재 상태

| 항목 | 내용 |
| --- | --- |
| 구분 | 직접 구현 |
| 상태 | 구현 중 |
| 진입 화면 | `/console/tips` |
| 결과 | 생성된 팁을 draft 상태로 저장 |

## 사용자 흐름

1. 사용자가 콘솔 팁 관리 화면에서 AI 생성 모달을 연다.
2. 카테고리, 태그, 프롬프트, 생성 개수를 입력한다.
3. Livewire 컴포넌트가 입력값을 검증하고 태그명을 정규화한다.
4. 프롬프트 빌더가 AI 요청 문장을 구성한다.
5. AI 생성 서비스가 `TipDraftData` 목록을 반환한다.
6. 저장 Action이 draft 팁을 생성하고 태그를 연결한다.
7. 생성 완료 toast를 표시하고 모달을 닫은 뒤 목록 화면을 갱신한다.

## 구현 구조

| 영역 | 파일 | 역할 |
| --- | --- | --- |
| 화면 상태 | `src/app/Livewire/Console/Tips/AiCreateTip.php` | 모달 입력 상태, 검증, 생성 흐름 조율 |
| 화면 | `src/resources/views/livewire/console/tips/ai-create-tip.blade.php` | AI 생성 모달 UI |
| 프롬프트 | `src/app/Services/Ai/Tip/BuildTipGenerationPrompt.php` | 카테고리/태그/사용자 요청을 AI 요청 문장으로 변환 |
| AI 생성 | `src/app/Services/Ai/Tip/GenerateTipsFromPrompt.php` | AI 응답을 팁 초안 데이터로 변환 |
| 일괄 저장 | `src/app/Actions/Tips/CreateAiGeneratedTips.php` | 여러 초안을 draft 팁으로 저장 |
| 단일 저장 | `src/app/Actions/Tips/CreateTip.php` | 팁 1개 저장과 태그 연결 |
| 테스트 | `src/tests/Feature/Ai/GenerateTipsFromPromptTest.php` | 생성 흐름 검증 |

## 구현 포인트

- Livewire 컴포넌트는 화면 상태와 사용자 액션 조율에 집중한다.
- 프롬프트 구성, AI 호출, 저장 흐름은 각각 별도 클래스로 분리했다.
- 생성 실패 시 예외 내용을 사용자에게 그대로 노출하지 않고 로그와 사용자 메시지를 분리한다.
- 생성 성공 후 toast, 모달 닫기, 목록 갱신을 한 흐름으로 처리한다.

## 검증

```bash
docker compose exec -T app php artisan test tests/Feature/Ai/GenerateTipsFromPromptTest.php
```

## 남은 작업

- 실제 AI provider 연결 설정 정리
- 생성 결과 미리보기와 선택 저장 UX
- 생성 이력과 실패 원인 추적
- rate limit과 비용 제어
