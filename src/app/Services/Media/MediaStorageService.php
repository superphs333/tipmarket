<?php
namespace App\Services\Media;

use App\Enums\MediaCollection;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * 미디어 파일 저장/삭제를 담당하는 공통 서비스
 *
 * [책임]
 * - 저장 disk 결정
 * - MediaPathGenerator를 사용해 저장 path 생성
 * - Laravel Storage를 통해 R2에 파일 업로드
 * - media 테이블에 메타데이터 저장
 * - R2 파일 삭제와 media 레코드 삭제 처리
 */
class MediaStorageService
{
    public function __construct(
        private readonly MediaPathGenerator $pathGenerator,
    ) {}

    /**
     * 업로드된 파일을 스토리지에 저장하고 media 레코드를 생성
     *
     * [기본흐름]
     * 1. 저장 disk 결정
     * 2. 저장 visibility 결정
     * 3. R2 내부 path 생성
     * 4. Storage disk에 실제 파일 업로드
     * 5. 이미지 크기 추출
     * 6. media 테이블에 메타데이터 저장
     */
    public function store(
        UploadedFile $file, // 사용자가 업로드한 파일
        MediaCollection $collection, // 파일 용도
        User $uploadedBy, // 파일을 업로드한 사용자
        ?Model $owner = null, // 파일이 연결될 모델 (User, Tip..)
        array $metadata = [], // 추가로 저장할 메타데이터
    ): Media // 생성된 media 모델
    {
        $disk = $this->disk();
        $visibility = $this->visibility($collection);
        $path = $this->pathGenerator->generate($file, $collection, $owner);

        $directory = dirname($path); // ex.profiles/15/avatar
        $filename = basename($path); // ex.01JZABC.webp

        /**
         * 흐름
         * 1. 먼저 R2에 업로드
         * 2. 그 다음 DB 저장
         * 3. DB 저장 실패 시 catch에서 R2 파일을 삭제해 보상 처리
         */
        try {
            $stored = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                $filename,
                ['visibility' => $visibility]
            );

            if ($stored === false) {
                throw new RuntimeException("Failed to store media file on disk [{$disk}].");
            }

            [$width, $height] = $this->imageDimensions($file);

            return Media::create([
                'disk' => $disk,
                'path' => $path,
                'collection' => $collection->value,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize() ?? 0,
                'width' => $width,
                'height' => $height,
                'owner_type' => $owner?->getMorphClass(),
                'owner_id' => $owner?->getKey(),
                'uploaded_by_id' => $uploadedBy->getKey(),
                'status' => $owner === null
                    ? Media::STATUS_TEMPORARY
                    : Media::STATUS_ATTACHED,
                'visibility' => $visibility,
                'metadata' => $metadata === [] ? null : $metadata,
            ]);
        } catch (Throwable $exception) {
            // db저장 중 예외 발생시, r2에 파일만 남는 것을 방지
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    /**
     * media 레코드와 연결된 실제 파일을 삭제
     *
     * [순서]
     * 1. R2 파일 삭제
     * 2. DB media 레코드 soft delete
     */
    public function delete(Media $media): void
    {
        $deleted = Storage::disk($media->disk)->delete($media->path); // ?? 문법

        if ($deleted === false) {
            throw new RuntimeException("Failed to delete media file [{$media->path}] from disk [{$media->disk}].");
        }

        $media->delete();
    }

    /**
     * media의 공개 URL 생성
     */
    public function url(Media $media): string
    {
        return $media->publicUrl();
    }

    /**
     * 저장에 사용할 disk 이름 가져오기
     *
     * config/media.php : 'disk' => env('MEDIA_DISK','r2')
     *
     * 운영에서는 보통 r2사용하고, 테스트나 로컬 상황에서는 public/local/fake disk로 바꿀 수 있음
     */
    private function disk(): string
    {
        return config('media.disk', 'r2');
    }

    /**
     * collection별 visibility 가져오기
     *
     * config/media.php
     */
    private function visibility(MediaCollection $collection): string
    {
        return config(
            "media.collections.{$collection->value}.visibility",
            Media::VISIBILITY_PUBLIC,
        );
    }

   /**
     * 이미지 파일의 width/height를 가져온다.
     *
     * getimagesize()는 이미지 파일이면 [width, height, ...] 형태의 배열을 반환한다.
     * 이미지가 아니거나 읽을 수 없으면 false를 반환할 수 있다.
     *
     * 현재 media 테이블에서 width/height는 nullable이므로,
     * 추출 실패 시 [null, null]을 반환한다.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return [null, null];
        }

        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            return [null, null];
        }

        return [
            $dimensions[0] ?? null,
            $dimensions[1] ?? null,
        ];
    }
}
