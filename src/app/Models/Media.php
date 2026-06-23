<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * 업로드된 파일 1개를 표현하는 모델
 *
 * - 이 모델은 파일 자체를 DB에 저장하지 않음.
 * => 실제 파일은 r2같은 외부 스토리지에 저장하고, DB에는 파일을 찾고 관리하기 위한 메타데이터만 저장
 * EX)
 *  - disk : r2
 *  - path : profiles/15/avatar/01JZABC.webp
 *  - collection : profile_avatar
 *
 * @property int $id 미디어 고유 식별자
 * @property string $disk Laravel filesystem disk 이름. 예: r2, public, local
 * @property string $path disk 내부 파일 경로. 예: profiles/15/avatar/example.webp
 * @property string $collection 파일 사용 용도. 예: profile_avatar, tip_thumbnail, tip_body
 * @property string|null $original_name 사용자가 업로드한 원본 파일명
 * @property string $mime_type 파일 MIME 타입. 예: image/jpeg, image/png, image/webp
 * @property int $size 파일 크기(byte)
 * @property int|null $width 이미지 너비(px)
 * @property int|null $height 이미지 높이(px)
 * @property string|null $owner_type 파일이 연결된 모델 클래스 또는 morph alias
 * @property int|null $owner_id 파일이 연결된 모델의 기본키
 * @property int|null $uploaded_by_id 파일을 업로드한 사용자 ID
 * @property string $status 파일 연결 상태. 예: temporary, attached, orphaned, deleted
 * @property string $visibility 파일 공개 범위. 예: public, private
 * @property array<string, mixed>|null $metadata 추가 메타데이터
 */

#[Fillable([
    'disk',
    'path',
    'collection',
    'original_name',
    'mime_type',
    'size',
    'width',
    'height',
    'owner_type',
    'owner_id',
    'uploaded_by_id',
    'status',
    'visibility',
    'metadata',
])]

class Media extends Model
{
    use SoftDeletes;

    /**
     * 상태
     */
    // 파일이 아직 특정 도메인 모델에 연결되지 않은 상태
        // - 에디터에서 이미지를 먼저 업로드했지만, 글 저장은 아직 안 된 경우
        // - 프로필 이미지 업로드 요청 중 DB 연결 전 단계
    public const STATUS_TEMPORARY  = 'temporary';
    // 파일에 실제 도메인 모델에 연결된 상태
    public const STATUS_ATTACHED = 'attached';
    // 연결 대상이 사라졌거나, 더 이상 사용되지 않는 상태
        // - 글은 삭제됐지만 파일 삭제 배치가 아직 처리하지 않은 경우
        // - 프로필 이미지 교체 후 이전 이미지가 정리 대기 중인 경우
    public const STATUS_ORPHANED = 'orphaned';

    /**
     * VISIBILITY
     */
    // 공개파일
    public const VISIBILITY_PUBLIC = 'public';
    // 비공개 파일
    public const VISIBILITY_PRIVATE = 'private';

    /**
     * 모델 attribute 타입 전환 (배열로 다루게 함 )
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * 관계
     */
    // 이 파일에 연결된 도메인 모델
    public function owner() : MorphTo
    {
        return $this->morphTo();
    }
    // 이 파일을 업로드한 사용자
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * 접근자
     */
    // 현재 파일의 실제 접근 URL
    public function publicUrl() : string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
    /**
     * 상태 확인
     */
    // 파일 공개 여부
    public function isPublic() : bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }
    // 파일이 비공개 파일인지 확인
    public function isPrivate(): bool
    {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }
    // 파일이 아직 임시 상태인지 확인
    public function isTemporary(): bool
    {
        return $this->status === self::STATUS_TEMPORARY;
    }
    // 파일이 실제 모델에 연결된 상태인지 확인
    public function isAttached(): bool
    {
        return $this->status === self::STATUS_ATTACHED;
    }
    // 파일이 정리 대기 상태인지 확인
    public function isOrphaned(): bool
    {
        return $this->status === self::STATUS_ORPHANED;
    }


}
