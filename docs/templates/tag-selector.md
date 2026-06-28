# 태그 선택기

태그 선택기는 태그 검색, 선택, 제거, hidden input 생성을 처리하는 재사용 UI 템플릿이다.

현재 화면에서는 Livewire 컴포넌트로 동작하고, 태그 검색 쿼리는 서비스 클래스로 분리해 재사용한다.

## 기본 사용

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
| `maxCount` | `5` | 선택 가능한 최대 태그 개수 |
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

## 내부 구조

| 경로 | 역할 |
| --- | --- |
| `resources/views/components/tags/selector.blade.php` | `<x-tags.selector />` 템플릿 입구 |
| `app/Livewire/Tags/TagSelector.php` | 검색어, 검색 상태, 선택 태그 상태 관리 |
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
