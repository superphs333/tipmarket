# TipMarket

TipMarket은 Laravel 기반의 문제 해결형 커뮤니티 서비스로 기획된 프로젝트다. PHP-FPM, Nginx, MariaDB, Redis를 분리한 Docker Compose 환경 위에서 Laravel 애플리케이션을 운영한다.

## 현재 구성

```text
외부 요청
  |
  v
tipmarket-web (Nginx)
  |
  +-- 정적 파일: ./public
  |
  +-- PHP 요청: tipmarket-php:9000
        |
        v
      ./src Laravel 애플리케이션

tipmarket-php  ---> tipmarket-db (MariaDB)
tipmarket-php  ---> tipmarket-redis (Redis)
```

## 주요 디렉터리

| 경로 | 역할 |
| --- | --- |
| `docker-compose.yml` | 로컬 서버 컨테이너, 네트워크, 볼륨 정의 |
| `docker/nginx/default.conf` | Nginx 가상 호스트와 PHP-FPM 연결 설정 |
| `docker/php/Dockerfile` | PHP 8.4-FPM, Composer, Node.js, Laravel 확장 구성 |
| `docker/php/conf.d/` | Xdebug, 업로드 크기 등 PHP 런타임 설정 |
| `public/` | 현재 Nginx가 바라보는 공개 루트 |
| `src/` | Laravel 애플리케이션 루트 |
| `docs/server-architecture.md` | 서버 구성 상세 문서 |
| `docs/common-commands.md` | 변경 유형별 자주 쓰는 Docker/Laravel 명령어 |
| `docs/media-storage.md` | 미디어 업로드 저장 구조와 개발자 사용법 |

## 컨테이너

| 서비스 | 컨테이너 | 역할 |
| --- | --- | --- |
| `app` | `tipmarket-php` | Laravel/PHP 실행, Composer, Node.js, Xdebug 포함 |
| `web` | `tipmarket-web` | Nginx 웹 서버, 외부 프록시 네트워크와 내부망 연결 |
| `db` | `tipmarket-db` | MariaDB 10.11 데이터베이스 |
| `redis` | `tipmarket-redis` | 캐시/세션/큐 용도의 Redis |

## 네트워크와 포트

| 항목 | 값 | 설명 |
| --- | --- | --- |
| 외부 네트워크 | `proxy-nw` | 리버스 프록시가 붙는 외부 Docker 네트워크 |
| 내부 네트워크 | `tipmarket-internal` | PHP, Nginx, DB, Redis가 통신하는 서비스 전용 네트워크 |
| DB 로컬 포트 | `127.0.0.1:3310 -> 3306` | 호스트에서만 MariaDB 접속 가능 |
| Vite 개발 포트 | `5175 -> 5173` | Laravel/Vite 개발 서버용 포트 |
| 도메인 | `tipmarket.com` | Nginx `server_name` 기준 로컬 도메인 |

## 로컬 실행 전 확인

`.env.example`을 기준으로 `.env`를 준비한다.

```bash
cp .env.example .env
docker compose config
docker compose up -d --build
```

Laravel 애플리케이션 코드는 `src/` 아래에서 관리한다.

## 미디어 업로드

이미지 업로드는 `MediaStorageService`와 `Media` 모델을 통해 처리한다. 실제 파일은 Laravel filesystem disk에 저장하고, DB에는 `media` 테이블로 메타데이터만 저장한다.

자세한 저장 흐름, 다이어그램, 개발자 사용법은 [미디어 저장 구조](docs/media-storage.md)를 참고한다.

## 재사용 UI 템플릿

태그 선택기는 일반 Blade 폼과 Livewire 부모 컴포넌트 양쪽에서 재사용한다. 기본 선택 개수 제한은 없고, 화면별 제한이 필요하면 `:max-count`와 부모 검증 규칙을 함께 지정한다.

기존 태그는 `tag_ids[]`, 신규 태그 후보는 `new_tag_names[]`로 전송한다. Livewire 부모 컴포넌트에서는 기본적으로 기존 태그 id 배열을 동기화하고, AI 생성처럼 태그명 배열이 필요한 화면에서는 `value-mode="names"`를 사용한다.

```blade
<livewire:tags.tag-selector
    wire:model="tagIds"
    :max-count="5"
/>
```

상세 사용법은 [태그 선택기 문서](docs/templates/tag-selector.md)를 참고한다.

## 추가 문서

- [서버 구성 상세](docs/server-architecture.md)
- [자주 쓰는 명령어](docs/common-commands.md)
- [미디어 저장 구조](docs/media-storage.md)
- [UI 템플릿 사용법](docs/templates/README.md)
