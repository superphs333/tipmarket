# TipMarket 자주 쓰는 명령어

이 문서는 TipMarket에서 서버 구성, Docker 설정, Laravel 애플리케이션, 프론트엔드 리소스를 변경했을 때 자주 쓰는 명령어를 정리한다. 명령은 서비스 루트(`/home/devl333/infrastructure/services/tipmarket`)에서 실행하는 것을 기준으로 한다.

## 기본 상태 확인

작업 전후로 현재 변경사항과 Compose 설정을 먼저 확인한다.

```bash
git status --short --ignored
docker compose config
```

컨테이너 상태를 확인한다.

```bash
docker compose ps
```

## 처음 실행하거나 Dockerfile을 바꿨을 때

PHP 이미지, 시스템 패키지, PHP 확장, Node.js, Composer 구성이 바뀌면 다시 빌드한다.

```bash
docker compose up -d --build
```

특정 서비스만 다시 빌드할 때는 서비스명을 지정한다.

```bash
docker compose build app
docker compose up -d app
```

## `docker-compose.yml`을 바꿨을 때

포트, 볼륨, 네트워크, 환경 변수, 서비스 구성이 바뀌면 먼저 설정을 검증한다.

```bash
docker compose config
```

설정이 정상이라면 컨테이너를 갱신한다.

```bash
docker compose up -d
```

컨테이너 재생성이 필요하면 다음 명령을 사용한다.

```bash
docker compose up -d --force-recreate
```

## Nginx 설정을 바꿨을 때

`docker/nginx/default.conf`를 수정한 뒤에는 Nginx 설정을 검사한다.

```bash
docker compose exec web nginx -t
```

설정이 정상이라면 Nginx를 재시작하거나 reload한다.

```bash
docker compose restart web
```

```bash
docker compose exec web nginx -s reload
```

## PHP 설정을 바꿨을 때

`docker/php/conf.d/*.ini`를 수정한 뒤에는 PHP 컨테이너를 재시작한다.

```bash
docker compose restart app
```

PHP 모듈과 설정을 확인한다.

```bash
docker compose exec app php -v
docker compose exec app php -m
docker compose exec app php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"
```

## 로그 확인

전체 로그를 확인한다.

```bash
docker compose logs -f
```

서비스별 로그를 확인한다.

```bash
docker compose logs -f web
docker compose logs -f app
docker compose logs -f db
docker compose logs -f redis
```

최근 로그만 확인할 때는 줄 수를 제한한다.

```bash
docker compose logs --tail=100 web
docker compose logs --tail=100 app
```

## 컨테이너 안에서 명령 실행

PHP 컨테이너 셸에 들어간다.

```bash
docker compose exec app bash
```

Nginx 컨테이너 셸에 들어간다.

```bash
docker compose exec web sh
```

MariaDB에 접속한다. 비밀번호는 `.env` 값을 사용한다.

```bash
docker compose exec db sh -lc 'mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

Redis 연결을 확인한다.

```bash
docker compose exec redis redis-cli ping
```

## Laravel 앱이 생성된 뒤 자주 쓰는 명령

현재 `src/`는 Laravel 애플리케이션이 들어갈 예정인 루트다. 아래 명령은 `src/`에 Laravel 앱이 생성된 뒤 사용한다.

Composer 의존성을 설치한다.

```bash
docker compose exec app composer install
```

Artisan 명령을 실행한다.

```bash
docker compose exec app php artisan about
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate
docker compose exec app php artisan test
```

캐시를 비운다.

```bash
docker compose exec app php artisan optimize:clear
```

Laravel 앱 키를 생성한다.

```bash
docker compose exec app php artisan key:generate
```

## 프론트엔드 리소스를 바꿨을 때

Laravel 앱 생성 후 `package.json`이 있는 상태에서 사용한다.

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

Vite 개발 서버를 컨테이너에서 띄울 때는 외부 접속을 위해 host를 지정한다.

```bash
docker compose exec app npm run dev -- --host 0.0.0.0
```

현재 Compose 포트 매핑은 `5175:5173`이다.

## DB를 초기화하거나 마이그레이션을 다시 볼 때

Laravel 앱 생성 후 개발 데이터베이스를 새로 만들 때 사용한다. 실제 데이터가 있으면 삭제될 수 있으므로 로컬 개발 환경에서만 사용한다.

```bash
docker compose exec app php artisan migrate:fresh
```

시더까지 다시 실행한다.

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## 컨테이너를 정리할 때

컨테이너를 중지한다.

```bash
docker compose down
```

볼륨까지 삭제하면 MariaDB 데이터가 사라진다.

```bash
docker compose down -v
```

사용하지 않는 이미지를 정리한다.

```bash
docker image prune
```

## 문서만 바꿨을 때

문서 변경은 공백 오류를 확인한다.

```bash
git diff --check -- .
```
