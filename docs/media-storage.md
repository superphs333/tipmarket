# 미디어 저장 구조

이 문서는 TipMarket의 미디어 업로드 저장 계층을 설명한다. 현재 미디어 계층은 프로필 이미지, 팁 썸네일, 질문 썸네일 같은 업로드 파일을 공통 방식으로 저장하기 위한 기반 코드다.

실제 이미지 파일은 Laravel filesystem disk에 저장하고, DB에는 파일을 찾고 관리하기 위한 메타데이터만 저장한다.

## 전체 저장 흐름

```mermaid
flowchart TD
    A[Controller / Domain Service] --> B[MediaStorageService::store]
    B --> C[config/media.php 조회]
    C --> C1[MEDIA_DISK 기본값: r2]
    C --> C2[collection별 visibility]

    B --> D[MediaPathGenerator::generate]
    D --> E[ULID 기반 파일명 생성]
    E --> F{MediaCollection}

    F -->|ProfileAvatar| G[profiles/{user_id}/avatar/{ulid}.{ext}]
    F -->|TipThumbnail + owner 있음| H[tips/{tip_id}/thumbnail/{ulid}.{ext}]
    F -->|TipThumbnail + owner 없음| I[media/temporary/tips/thumbnail/{ulid}.{ext}]
    F -->|QuestionThumbnail + owner 있음| J[questions/{question_id}/thumbnail/{ulid}.{ext}]
    F -->|QuestionThumbnail + owner 없음| K[media/temporary/questions/thumbnail/{ulid}.{ext}]

    G --> L[Storage::disk(...)->putFileAs]
    H --> L
    I --> L
    J --> L
    K --> L

    L --> M[이미지 width / height 추출]
    M --> N[media 테이블에 메타데이터 저장]
    N --> O[Media 모델 반환]
```

## 구성 파일

| 파일 | 역할 |
| --- | --- |
| `src/app/Enums/MediaCollection.php` | 미디어 용도 값을 enum으로 관리한다. |
| `src/app/Models/Media.php` | 저장된 파일의 DB 메타데이터, 상태 확인, 공개 URL 생성을 담당한다. |
| `src/app/Services/Media/MediaStorageService.php` | 파일 저장, DB 레코드 생성, 파일 삭제를 담당한다. |
| `src/app/Services/Media/MediaPathGenerator.php` | 원본 파일명 대신 ULID 기반 저장 경로를 생성한다. |
| `src/config/media.php` | 저장 disk와 용도별 업로드 정책을 모아둔다. |
| `src/database/migrations/*_create_media_table.php` | `media` 테이블을 생성한다. |

## 저장 위치

```mermaid
flowchart TD
    A[MediaCollection] --> B[profile_avatar]
    A --> C[tip_thumbnail]
    A --> D[question_thumbnail]

    B --> B1[owner 필수]
    B1 --> B2[profiles/{user_id}/avatar/{ulid}.{ext}]

    C --> C1{owner 있음?}
    C1 -->|예| C2[tips/{tip_id}/thumbnail/{ulid}.{ext}]
    C1 -->|아니오| C3[media/temporary/tips/thumbnail/{ulid}.{ext}]

    D --> D1{owner 있음?}
    D1 -->|예| D2[questions/{question_id}/thumbnail/{ulid}.{ext}]
    D1 -->|아니오| D3[media/temporary/questions/thumbnail/{ulid}.{ext}]
```

파일명은 `Str::ulid()`와 업로드 파일 확장자를 조합해서 만든다. 원본 파일명은 저장 경로에 사용하지 않고, 추적용 메타데이터로만 `media.original_name`에 저장한다.

## media 테이블 역할

```mermaid
flowchart LR
    A[업로드 이미지 파일] --> B[Laravel Storage Disk]
    A --> C[media 테이블]

    B --> B1[실제 바이너리 파일]
    B --> B2[path 기준 접근]

    C --> C1[disk / path / collection]
    C --> C2[owner_type / owner_id]
    C --> C3[uploaded_by_id]
    C --> C4[status / visibility]
    C --> C5[mime_type / size / width / height]

    C --> D[Media 모델]
    D --> E[publicUrl()]
    E --> F[Storage::disk(...)->url(path)]
```

`status`는 파일 연결 상태를 나타낸다.

| 상태 | 의미 |
| --- | --- |
| `temporary` | 아직 특정 도메인 모델에 연결되지 않은 임시 파일 |
| `attached` | 사용자, 팁, 질문 같은 owner 모델에 연결된 파일 |
| `orphaned` | owner 삭제 또는 이미지 교체로 정리 대기 중인 파일 |

## 개발자 사용법

컨트롤러는 파일 업로드 요청을 직접 저장하지 말고 `FormRequest`에서 검증한 뒤 `MediaStorageService`를 호출한다. `MediaStorageService`는 Laravel service container로 주입해서 사용한다.

```php
use App\Enums\MediaCollection;
use App\Services\Media\MediaStorageService;

public function updateAvatar(UpdateAvatarRequest $request, MediaStorageService $mediaStorage)
{
    $media = $mediaStorage->store(
        file: $request->file('avatar'),
        collection: MediaCollection::ProfileAvatar,
        uploadedBy: $request->user(),
        owner: $request->user(),
    );

    return back();
}
```

팁이나 질문 본문을 저장하기 전에 썸네일을 먼저 업로드해야 하는 흐름에서는 owner 없이 임시 파일로 저장할 수 있다.

```php
$media = $mediaStorage->store(
    file: $request->file('thumbnail'),
    collection: MediaCollection::TipThumbnail,
    uploadedBy: $request->user(),
);
```

owner 없이 저장된 파일은 `temporary` 상태로 생성된다. 글이나 질문 모델이 생성된 뒤에는 별도의 연결 로직에서 owner 정보를 채우고 `attached` 상태로 바꿔야 한다.

```php
$media->update([
    'owner_type' => $tip->getMorphClass(),
    'owner_id' => $tip->getKey(),
    'status' => \App\Models\Media::STATUS_ATTACHED,
]);
```

파일을 삭제할 때도 직접 `Storage`와 `Media` 모델을 따로 다루지 말고 `MediaStorageService::delete()`를 사용한다.

```php
$mediaStorage->delete($media);
```

삭제 순서는 실제 파일 삭제 후 `media` 레코드 soft delete다. 파일 삭제가 실패하면 예외가 발생하고 DB 레코드는 삭제하지 않는다.

## 검증 책임

`src/config/media.php`에는 collection별 `max_size`, `mimes`, `visibility` 정책이 정의되어 있지만, 현재 `MediaStorageService`가 직접 파일 검증을 수행하지는 않는다. 컨트롤러 진입 전 `FormRequest`에서 용도에 맞게 검증해야 한다.

```php
public function rules(): array
{
    return [
        'avatar' => [
            'required',
            'image',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:2048',
        ],
    ];
}
```

## 아직 추가되어야 할 흐름

현재 미디어 계층은 저장 기반이다. 아래 흐름은 기능 구현 단계에서 별도 서비스나 액션으로 추가한다.

- 임시 파일을 owner 모델에 연결하는 attach 로직
- 프로필 이미지 교체 시 이전 이미지 orphan 처리
- 오래된 `temporary` 파일 정리 배치
- 비공개 파일의 signed URL 또는 접근 권한 제어
- 업로드 성공/실패에 대한 Feature 테스트
