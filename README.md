# TipMarket

TipMarket은 Laravel 기반의 문제 해결형 커뮤니티 서비스로 기획된 프로젝트다. 현재 저장소는 애플리케이션 코드보다 먼저 Docker Compose 기반 로컬 서버 골격을 구성한 상태이며, PHP-FPM, Nginx, MariaDB, Redis를 분리해서 운영한다.

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
      ./src Laravel 애플리케이션 예정

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
| `src/` | Laravel 애플리케이션이 들어갈 예정인 루트 |
| `docs/server-architecture.md` | 서버 구성 상세 문서 |

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

현재 `src/` Laravel 애플리케이션은 아직 비어 있으므로, 실제 Laravel 앱을 생성하기 전에는 `public/index.html` 정적 시작 화면이 기본 진입점이다.

## 추가 문서

- [서버 구성 상세](docs/server-architecture.md)
