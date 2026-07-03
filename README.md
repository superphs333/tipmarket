# TipMarket

TipMarket은 생활 문제를 질문하고 해결 팁을 공유하는 문제 해결형 커뮤니티 서비스다. Laravel 기반으로 인증, 운영 콘솔, 팁 관리, 태그/미디어, AI 팁 생성 흐름을 단계적으로 구현하고 있다.

이 README는 포트폴리오 검토자가 프로젝트의 목적, 직접 구현한 기능, 기술 선택 이유를 빠르게 파악할 수 있도록 구성한다. 서버와 개발 환경의 상세 내용은 `docs/` 문서로 분리한다.

## 프로젝트 목표

- 생활 문제를 질문/팁/답변 중심으로 정리하는 커뮤니티 서비스 구현
- Laravel의 인증, 정책, 미들웨어, Eloquent 관계, Livewire를 실제 기능 흐름에 맞게 적용
- 도메인 로직을 Controller에 몰아넣지 않고 Action, Service, Model, Policy로 책임 분리
- Docker Compose 기반의 재현 가능한 개발/운영 환경 구성
- 향후 질문/답변, 댓글, 좋아요, 북마크, 알림, 신고, 게이미피케이션, AI 추천 기능까지 확장 가능한 기반 마련

## 주요 기능

Laravel starter kit이 제공하는 기본 인증 기능은 대표 기능으로 내세우지 않고, TipMarket 요구사항에 맞게 직접 구현하거나 확장한 부분만 정리한다.

| 기능 | 구분 | 현재 상태 | 핵심 구현 | 상세 문서 |
| --- | --- | --- | --- | --- |
| 역할 기반 콘솔 | 직접 구현 | 구현 중 | 역할 모델, 콘솔 접근 미들웨어, 정책 기반 메뉴 접근 | [콘솔](docs/features/console.md) |
| 팁 관리 | 직접 구현 | 구현 중 | 팁 모델, 카테고리/태그 연결, draft 저장 Action | [팁 관리](docs/features/tips.md) |
| AI 팁 생성 | 직접 구현 | 구현 중 | Livewire 모달, 프롬프트 빌더, AI 생성 Service, 저장 Action | [AI 팁 생성](docs/features/ai-tip-generation.md) |
| 미디어/태그 | 직접 구현 | 일부 구현 | 미디어 저장 계층, 태그 검색/신규 후보 선택, 재사용 컴포넌트 | [미디어/태그](docs/features/media-tags.md) |
| 계정 설정 커스터마이징 | 확장/수정 | 일부 구현 | 프로필/보안 설정 화면, locale, 보안 UI 조정 | [계정 설정 커스터마이징](docs/features/account-customization.md) |

전체 기능 문서 인덱스는 [기능 문서](docs/features/README.md)를 참고한다.

## 대표 구현 포인트

### Action/Service 중심의 기능 분리

팁 저장과 AI 생성 흐름은 화면 코드와 분리했다. `CreateTip`은 draft 팁 저장과 태그 연결을 담당하고, `CreateAiGeneratedTips`는 AI가 반환한 여러 초안을 저장하는 유스케이스를 조율한다.

| 책임 | 주요 파일 |
| --- | --- |
| 팁 1개 저장 | `src/app/Actions/Tips/CreateTip.php` |
| AI 생성 팁 일괄 저장 | `src/app/Actions/Tips/CreateAiGeneratedTips.php` |
| AI 프롬프트 구성 | `src/app/Services/Ai/Tip/BuildTipGenerationPrompt.php` |
| AI 결과 생성 | `src/app/Services/Ai/Tip/GenerateTipsFromPrompt.php` |

### 팁 공개 정책

팁의 작성 상태와 접근 대상을 분리했다. `status`는 글의 생명주기만 나타내고, `audience`는 발행된 글을 누가 볼 수 있는지 나타낸다.

| 컬럼 | 값 | 의미 |
| --- | --- | --- |
| `status` | `draft`, `published` | 초안/발행 상태 |
| `audience` | `public`, `premium`, `private` | 전체 공개/프리미엄 공개/작성자 비공개 |

정상 조합은 아래 기준으로 제한한다.

| `audience` \ `status` | `draft` | `published` |
| --- | --- | --- |
| `public` | X | O |
| `premium` | X | O |
| `private` | O | O |

### 역할 기반 콘솔 접근

운영 콘솔은 로그인/이메일 인증 이후에도 역할을 한 번 더 확인한다. 콘솔 진입 권한과 팁 관리 권한을 분리해 이후 메뉴가 늘어날 때 세부 정책을 좁힐 수 있도록 했다.

| 책임 | 주요 파일 |
| --- | --- |
| 콘솔 접근 역할 정의 | `src/app/Models/Role.php` |
| 역할 확인 미들웨어 | `src/app/Http/Middleware/EnsureUserHasRole.php` |
| 팁 메뉴 정책 | `src/app/Policies/TipPolicy.php` |
| 콘솔 라우트 | `src/routes/web.php` |

### 미디어 저장 계층

업로드 파일과 DB 메타데이터를 분리했다. 실제 파일은 Laravel filesystem disk에 저장하고, `media` 테이블은 경로, 용도, owner, 업로드 사용자, 상태 같은 관리 정보를 저장한다.

상세 설계는 [미디어 저장 구조](docs/media-storage.md)를 참고한다.

### 재사용 태그 선택기

태그 검색, 기존 태그 선택, 신규 태그 후보 추가를 Livewire 컴포넌트로 분리했다. 태그 선택기는 DB 저장을 직접 하지 않고, 부모 폼이나 저장 Action이 `tag_names[]` 값을 검증한 뒤 실제 태그를 찾거나 생성한다.

상세 사용법은 [태그 선택기 문서](docs/templates/tag-selector.md)를 참고한다.

### 팁 목록 필터 재사용 기준

관리자 팁 관리처럼 다른 화면에서 팁 목록 검색/필터를 다시 사용할 때는 조회 쿼리와 화면 상태를 분리해서 붙인다. 실제 DB 조건은 `TipListQuery`가 담당하고, Livewire 화면은 `ManagesTipListFilters`가 검색 상태를 `filters` 배열로 변환해 넘긴다.

| 사용 범위 | 사용하는 파일 | 역할 |
| --- | --- | --- |
| 팁 목록 필터 기본 | `src/app/Queries/Tips/TipListQuery.php` | 카테고리, 작성자, 상태, 노출, 기간, 태그, 키워드 조건을 `Tip` 쿼리로 변환 |
| 입력값 정규화 | `src/app/Support/Filters/FilterNormalizer.php` | id, id 배열, 허용 문자열, 날짜, 키워드 값을 쿼리용 값으로 정리 |
| 태그명 정규화 | `src/app/Support/Tags/TagNameNormalizer.php` | `tag_names` 배열의 공백, `#`, 중복 값을 정리 |
| Livewire 필터 상태 | `src/app/Livewire/Concerns/ManagesTipListFilters.php` | `categoryId`, `tagNames`, `createdFrom`, `keyword` 같은 public property와 `search()`, `resetFilters()`, `tipListFilters()` 제공 |
| 현재 사용 예시 | `src/app/Livewire/Console/Tips/TipManagementList.php` | 관리자 팁 관리 화면에서 `TipListQuery::paginate($this->tipListFilters())` 호출 |
| 필터 UI 예시 | `src/resources/views/livewire/console/tips/tip-management-list.blade.php` | 카테고리, 태그, 기간, 검색어, 검색/초기화 UI |

태그 필터 UI까지 함께 재사용하려면 아래 파일을 같이 사용한다.

| 사용 범위 | 사용하는 파일 | 역할 |
| --- | --- | --- |
| 태그 선택 상태/검색 액션 | `src/app/Livewire/Tags/TagSelector.php` | 검색어, 검색 결과, 선택 태그, 신규 태그 후보 상태 관리 |
| 태그 검색 쿼리 | `src/app/Services/Tags/TagSearchService.php` | 활성 태그를 이름 기준으로 검색하고 사용량/이름순으로 정렬 |
| 태그 선택기 화면 | `src/resources/views/livewire/tags/tag-selector.blade.php` | 검색 입력, 결과 드롭다운, 선택된 태그 표시 |
| Blade 컴포넌트 진입점 | `src/resources/views/components/tags/selector.blade.php` | `<x-tags.selector />` 형태로 Livewire 태그 선택기를 감싸는 템플릿 |

새로운 Livewire 목록 파일에서 팁 필터를 붙일 때는 아래 순서로 구성한다.

1. Livewire 컴포넌트에 `ManagesTipListFilters`를 사용한다.
2. `render()` 또는 목록 조회 메서드에서 `TipListQuery::make()->paginate($this->tipListFilters(), 15)`를 호출한다.
3. Blade에서 필터 입력을 trait의 public property에 `wire:model`로 연결한다.
4. 검색 버튼은 `wire:click="search"`, 초기화 버튼은 `wire:click="resetFilters"`를 호출한다.
5. 태그 필터가 필요하면 `livewire:tags.tag-selector`를 `wire:model="tagNames"`로 연결하고, 검색 필터 용도에서는 `:allow-create="false"`를 사용한다.

```php
use App\Livewire\Concerns\ManagesTipListFilters;
use App\Queries\Tips\TipListQuery;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PublicTipList extends Component
{
    use ManagesTipListFilters;

    public function render(): View
    {
        return view('livewire.tips.public-tip-list', [
            'tips' => TipListQuery::make()
                ->paginate($this->tipListFilters(), 15),
        ]);
    }
}
```

```blade
<input
    type="search"
    wire:model="keyword"
    wire:keydown.enter.prevent="search"
    placeholder="검색어 입력"
>

<livewire:tags.tag-selector
    wire:model="tagNames"
    label=""
    placeholder="태그 이름 검색"
    name="tag_names"
    :allow-create="false"
    variant="compact"
/>

<button type="button" wire:click="resetFilters">초기화</button>
<button type="button" wire:click="search">검색</button>

@foreach ($tips as $tip)
    {{ $tip->title }}
@endforeach

{{ $tips->links() }}
```

이 필터 구조는 현재 `Tip` 목록에 맞춰져 있다. 질문/답변/사용자 목록처럼 다른 도메인의 필터가 필요하면 `FilterNormalizer` 같은 공통 정규화 도구만 재사용하고, 도메인별 `QuestionListQuery`, `ManagesQuestionListFilters`처럼 별도 쿼리/상태 클래스로 분리한다.

## 기술 스택과 선택 이유

| 기술 | 사용 이유 |
| --- | --- |
| Laravel | 인증, 라우팅, 미들웨어, Policy, Eloquent 관계, FormRequest/Action/Service 구조를 활용하기 위해 선택 |
| Livewire | 콘솔 모달, 태그 선택기처럼 서버 상태와 UI 상호작용이 밀접한 화면을 빠르게 구현하기 위해 사용 |
| MariaDB | 사용자, 역할, 팁, 카테고리, 태그, 미디어 메타데이터처럼 관계형 데이터가 많은 도메인에 적합 |
| Redis | 캐시, 세션, 큐 확장 가능성을 고려한 런타임 구성 |
| Docker Compose | PHP-FPM, Nginx, MariaDB, Redis를 분리해 로컬/운영 환경을 일관되게 구성 |

## 현재 서버 구성

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

상세 서버 구성은 [서버 구성 상세](docs/server-architecture.md)를 참고한다.

## 주요 디렉터리

| 경로 | 역할 |
| --- | --- |
| `src/` | Laravel 애플리케이션 루트 |
| `docs/features/` | 포트폴리오 관점의 기능별 구현 문서 |
| `docs/server-architecture.md` | 서버 구성 상세 문서 |
| `docs/common-commands.md` | 변경 유형별 자주 쓰는 Docker/Laravel 명령어 |
| `docs/media-storage.md` | 미디어 업로드 저장 구조와 개발자 사용법 |
| `docs/templates/` | 재사용 Blade/Livewire UI 템플릿 문서 |
| `docker-compose.yml` | 로컬 서버 컨테이너, 네트워크, 볼륨 정의 |
| `docker/nginx/default.conf` | Nginx 가상 호스트와 PHP-FPM 연결 설정 |
| `docker/php/Dockerfile` | PHP 8.4-FPM, Composer, Node.js, Laravel 확장 구성 |

## 로컬 실행 전 확인

`.env.example`을 기준으로 `.env`를 준비한다.

```bash
cp .env.example .env
docker compose config
docker compose up -d --build
```

Laravel 애플리케이션 코드는 `src/` 아래에서 관리한다.

자주 쓰는 개발/운영 명령어는 [자주 쓰는 명령어](docs/common-commands.md)를 참고한다.

## 테스트와 검증

변경 유형에 따라 아래 명령을 우선 확인한다.

```bash
git diff --check -- .
docker compose config
docker compose exec -T app php artisan test
```

프론트엔드 빌드가 필요한 변경은 다음 명령을 추가한다.

```bash
docker compose exec -T app npm run build
```

## 추가 문서

- [기능 문서](docs/features/README.md)
- [서버 구성 상세](docs/server-architecture.md)
- [자주 쓰는 명령어](docs/common-commands.md)
- [미디어 저장 구조](docs/media-storage.md)
- [UI 템플릿 사용법](docs/templates/README.md)
