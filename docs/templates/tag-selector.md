# 태그 선택기

태그 선택기는 태그 검색, 선택, 제거, hidden input 생성을 처리하는 재사용 UI 템플릿이다.

현재 화면에서는 Livewire 컴포넌트로 동작하고, 태그 검색 쿼리는 서비스 클래스로 분리해 재사용한다. 태그 선택기는 저장을 직접 처리하지 않고, 부모 폼이나 부모 Livewire 컴포넌트에 선택된 태그 id 목록을 넘기는 입력 부품 역할만 한다.

## 일반 Blade 폼 사용

```blade
<x-tags.selector />
```

위 코드를 폼 안에 넣으면 태그 선택기를 바로 사용할 수 있다.

## 옵션 사용

```blade
<x-tags.selector
    label="관련 태그"
    placeholder="태그 이름을 입력하세요"
    name="tag_ids"
    :max-count="5"
/>
```

## 옵션 설명

| 옵션 | 기본값 | 설명 |
| --- | --- | --- |
| `label` | `태그` | 태그 선택기 상단에 표시할 라벨 |
| `placeholder` | `태그 이름 검색...` | 검색 입력창 안내 문구 |
| `name` | `tag_ids` | 폼 제출 시 사용할 input 이름 |
| `maxCount` | `null` | 선택 가능한 최대 태그 개수. `null`이면 제한 없음 |
| `selected` | `[]` | 수정 화면에서 미리 선택해 둘 태그 목록 |

## 수정 화면에서 기존 태그 전달

```blade
<x-tags.selector
    :selected="$tip->tags"
/>
```

기존 글 수정 화면처럼 이미 연결된 태그가 있다면 `selected`에 태그 목록을 넘긴다.

## 폼 전송 값

선택된 태그는 hidden input으로 렌더링된다.

```html
<input type="hidden" name="tag_ids[]" value="1">
<input type="hidden" name="tag_ids[]" value="2">
```

컨트롤러에서는 아래처럼 받을 수 있다.

```php
$tagIds = $request->input('tag_ids', []);
```

## Livewire 부모 컴포넌트에서 사용

Livewire 화면에서는 부모 컴포넌트가 태그 id 배열을 소유하고, 태그 선택기는 `wire:model`로 그 값만 동기화한다.

```blade
<livewire:tags.tag-selector wire:model="tagIds" />
```

부모 Livewire 컴포넌트에는 같은 이름의 public property를 둔다.

```php
public array $tagIds = [];
```

검증 예시는 아래와 같다.

```php
$this->validate([
    'tagIds' => ['array'],
    'tagIds.*' => ['integer', 'exists:tags,id'],
]);
```

## 선택 개수 제한

기본값은 제한 없음이다. 사용처에서 제한이 필요할 때만 `max-count`를 넘긴다.

```blade
<livewire:tags.tag-selector
    wire:model="tagIds"
    :max-count="5"
/>
```

이 경우 부모 Livewire 검증에도 같은 제한을 둔다.

```php
$this->validate([
    'tagIds' => ['array', 'max:5'],
    'tagIds.*' => ['integer', 'exists:tags,id'],
]);
```

숫자를 한 곳에서 관리하려면 부모 컴포넌트에 프로퍼티를 둔다.

```php
public int $maxTagCount = 5;
```

```blade
<livewire:tags.tag-selector
    wire:model="tagIds"
    :max-count="$maxTagCount"
/>
```

```php
$this->validate([
    'tagIds' => ['array', 'max:'.$this->maxTagCount],
    'tagIds.*' => ['integer', 'exists:tags,id'],
]);
```

UI 제한과 서버 검증은 함께 적용한다. UI만 제한하면 요청 조작으로 우회할 수 있고, 서버 검증만 두면 사용자가 제한을 늦게 알게 된다.

## 내부 구조

| 경로 | 역할 |
| --- | --- |
| `resources/views/components/tags/selector.blade.php` | `<x-tags.selector />` 템플릿 입구 |
| `app/Livewire/Tags/TagSelector.php` | 검색어, 검색 상태, 선택 태그 상태 관리, `#[Modelable]` 값 동기화 |
| `resources/views/livewire/tags/tag-selector.blade.php` | 실제 태그 선택기 화면 |
| `app/Services/Tags/TagSearchService.php` | 태그 검색 쿼리 |

## 분석 순서

태그 선택기 구조를 분석할 때는 아래 순서로 본다.

```text
resources/views/components/tags/selector.blade.php
-> app/Livewire/Tags/TagSelector.php
-> resources/views/livewire/tags/tag-selector.blade.php
-> app/Services/Tags/TagSearchService.php
```
