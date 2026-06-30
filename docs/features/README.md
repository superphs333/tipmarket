# 기능 문서

TipMarket에서 직접 구현하거나 프로젝트 요구에 맞게 확장한 기능을 정리한다.

Laravel starter kit이 기본 제공하는 회원가입, 로그인, 비밀번호 재설정 같은 인증 기능은 대표 기능으로 다루지 않는다. 이 문서는 TipMarket 도메인을 위해 직접 설계한 기능과 starter kit 기반 코드 중 직접 수정한 부분만 기록한다.

## 기능 구분

| 구분 | 의미 |
| --- | --- |
| 직접 구현 | TipMarket 도메인 요구사항에 맞게 직접 설계하고 구현한 기능 |
| 확장/수정 | starter kit 또는 패키지 기반 코드 중 프로젝트 요구에 맞게 직접 변경한 부분 |

## 기능 목록

| 기능 | 구분 | 현재 상태 | 설명 | 상세 문서 |
| --- | --- | --- | --- | --- |
| 역할 기반 콘솔 | 직접 구현 | 구현 중 | 운영자/관리자 역할에 따라 콘솔과 팁 관리 메뉴 접근을 제어 | [console.md](console.md) |
| 팁 관리 | 직접 구현 | 구현 중 | 팁 draft 저장, 카테고리/태그 연결, 향후 공개/숨김 상태 확장 기반 | [tips.md](tips.md) |
| AI 팁 생성 | 직접 구현 | 구현 중 | 콘솔 모달에서 입력한 조건으로 AI 팁 초안을 생성하고 draft 팁으로 저장 | [ai-tip-generation.md](ai-tip-generation.md) |
| 미디어/태그 | 직접 구현 | 일부 구현 | 업로드 파일 메타데이터 관리, 태그 검색/선택/신규 후보 입력 | [media-tags.md](media-tags.md) |
| 계정 설정 커스터마이징 | 확장/수정 | 일부 구현 | starter kit 기반 설정 화면에 프로필, locale, 보안 UI 개선을 반영 | [account-customization.md](account-customization.md) |

## 문서 작성 기준

- 구현한 기능과 예정 기능을 구분한다.
- Laravel 기본 제공 기능을 직접 구현한 것처럼 표현하지 않는다.
- 각 기능 문서는 목적, 사용자 흐름, 구현 구조, 검증 방법, 남은 작업을 포함한다.
- 기능이 바뀌면 루트 `README.md`의 주요 기능 표와 이 문서를 함께 갱신한다.

## 현재 문서 구조

```text
README.md                         # 포트폴리오 메인 문서
docs/
  features/
    README.md                      # 기능 문서 인덱스
    console.md                     # 역할 기반 콘솔
    tips.md                        # 팁 관리
    ai-tip-generation.md           # AI 팁 생성
    media-tags.md                  # 미디어/태그
    account-customization.md       # 계정 설정 커스터마이징
  server-architecture.md           # 서버 구성
  common-commands.md               # 개발/운영 명령어
  media-storage.md                 # 미디어 저장 상세
  templates/
    README.md                      # 재사용 UI 템플릿 인덱스
    tag-selector.md                # 태그 선택기 상세
  assets/
    screenshots/                   # 화면 캡처
    diagrams/                      # 다이어그램
```
