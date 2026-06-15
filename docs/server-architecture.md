# TipMarket 서버 구성

이 문서는 현재 저장소에 실제로 구성된 Docker 기반 서버 구조를 기준으로 정리한다. 아직 Laravel 애플리케이션 본체는 `src/`에 생성되지 않았으므로, 기능 구현 상태가 아니라 서버 실행 골격을 설명한다.

## 한눈에 보는 구조

```text
사용자 / 브라우저
  |
  | HTTP
  v
proxy-nw
  |
  v
tipmarket-web
  |  Nginx
  |
  +-- /var/www/html/public
  |     |
  |     +-- ./public
  |
  +-- FastCGI: tipmarket-php:9000
        |
        v
      tipmarket-php
        |
        +-- /var/www/html
        |     |
        |     +-- ./src
        |
        +-- tipmarket-db:3306
        |
        +-- tipmarket-redis:6379
```

## 서비스별 역할

| Compose 서비스 | 컨테이너명 | 이미지/빌드 | 네트워크 | 역할 |
| --- | --- | --- | --- | --- |
| `app` | `tipmarket-php` | `docker/php/Dockerfile` | `tipmarket-internal` | PHP-FPM으로 Laravel 로직을 실행하는 애플리케이션 런타임 |
| `web` | `tipmarket-web` | `nginx:1.24-alpine` | `proxy-nw`, `tipmarket-internal` | 외부 HTTP 요청을 받고 정적 파일 또는 PHP-FPM으로 전달 |
| `db` | `tipmarket-db` | `mariadb:10.11` | `tipmarket-internal` | 애플리케이션 데이터 저장소 |
| `redis` | `tipmarket-redis` | `redis:7.2-alpine` | `tipmarket-internal` | 캐시, 세션, 큐 등에 사용할 Redis |

## 요청 처리 흐름

1. 브라우저가 `tipmarket.com`으로 요청을 보낸다.
2. 외부 리버스 프록시 또는 Docker 네트워크가 요청을 `tipmarket-web`으로 전달한다.
3. Nginx는 `./public`을 `/var/www/html/public`으로 읽는다.
4. 정적 파일이 있으면 Nginx가 바로 응답한다.
5. PHP 요청은 `fastcgi_pass tipmarket-php:9000`을 통해 PHP-FPM 컨테이너로 전달한다.
6. Laravel 앱이 `src/`에 구성되면 PHP-FPM은 `/var/www/html` 기준으로 애플리케이션 코드를 실행한다.
7. 애플리케이션은 내부 네트워크에서 `db`, `redis` 서비스명으로 MariaDB와 Redis에 접근한다.

## 볼륨 마운트

| 호스트 경로/볼륨 | 컨테이너 경로 | 사용 서비스 | 설명 |
| --- | --- | --- | --- |
| `./src` | `/var/www/html` | `app` | Laravel 애플리케이션 코드 위치 |
| `./public` | `/var/www/html/public` | `web` | Nginx 공개 루트 |
| `./docker/nginx/default.conf` | `/etc/nginx/conf.d/default.conf` | `web` | Nginx 서버 설정 |
| `./docker/php/conf.d/xdebug.ini` | `/usr/local/etc/php/conf.d/99-xdebug.ini` | `app` | Xdebug 로컬 디버깅 설정 |
| `./docker/php/conf.d/uploads.ini` | `/usr/local/etc/php/conf.d/90-uploads.ini` | `app` | 업로드 크기와 메모리 제한 설정 |
| `tipmarket_db_data` | `/var/lib/mysql` | `db` | MariaDB 영구 데이터 볼륨 |

## 네트워크

| 네트워크 | 타입 | 연결 서비스 | 목적 |
| --- | --- | --- | --- |
| `proxy-nw` | external | `web` | 외부 리버스 프록시와 연결되는 공유 네트워크 |
| `tipmarket-internal` | bridge | `app`, `web`, `db`, `redis` | TipMarket 내부 통신 전용 네트워크 |

`db`와 `redis`는 `proxy-nw`에 붙지 않는다. 외부 요청은 Nginx를 거치고, 애플리케이션 내부 통신은 `tipmarket-internal`에서 처리하는 구조다.

## 포트와 접근 지점

| 항목 | 설정 | 설명 |
| --- | --- | --- |
| Nginx | 컨테이너 내부 `80` | 외부 노출은 `proxy-nw`에 연결된 프록시 구성을 따른다. |
| MariaDB | `127.0.0.1:3310:3306` | 호스트 로컬에서만 DB 접속을 허용한다. |
| Vite | `5175:5173` | Laravel 프론트엔드 개발 서버를 위한 포트 매핑이다. |
| Xdebug | `9003` | `host.docker.internal`을 통해 IDE와 연결한다. |

## PHP 런타임

`docker/php/Dockerfile`은 `php:8.4-fpm`을 기반으로 한다.

포함된 주요 도구와 확장:

- Composer
- Node.js 22.x
- `pdo_mysql`
- `gd`
- `zip`
- `bcmath`
- `redis`
- `xdebug`

업로드 관련 PHP 설정은 `docker/php/conf.d/uploads.ini`에서 관리한다.

```ini
upload_max_filesize=12M
post_max_size=12M
memory_limit=256M
```

## 환경 변수

루트 `.env.example`은 Docker Compose와 Laravel 앱에서 같이 사용할 기본 값을 담고 있다.

| 변수 그룹 | 주요 값 | 용도 |
| --- | --- | --- |
| UID/GID | `UID`, `GID` | 컨테이너 사용자 권한 기준값 |
| MySQL | `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` | MariaDB 컨테이너 초기화 |
| Laravel DB | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Laravel 데이터베이스 연결 |
| Xdebug | `XDEBUG_MODE`, `XDEBUG_CLIENT_HOST`, `XDEBUG_PORT` | 로컬 디버깅 |
| App | `APP_NAME`, `APP_ENV`, `APP_URL` | Laravel 애플리케이션 기본 설정 |

실제 비밀번호와 키 값은 `.env`에만 두고 문서나 커밋 대상에 포함하지 않는다.

## 현재 상태와 다음 단계

현재 서버 골격은 준비되어 있지만 `src/` Laravel 애플리케이션은 아직 비어 있다. 따라서 지금 기준의 진입점은 `public/index.html`이며, Laravel 생성 후에는 Nginx 루트와 PHP-FPM 처리 흐름이 Laravel의 `public/index.php` 중심으로 동작하게 된다.

다음 단계 후보:

1. `src/`에 Laravel 애플리케이션 생성
2. `.env.example`을 Laravel `src/.env.example`과 역할별로 정리
3. Nginx `root`를 Laravel 앱의 `public` 경로 기준으로 재검토
4. `docker compose config`와 Laravel 기본 페이지 응답 확인
