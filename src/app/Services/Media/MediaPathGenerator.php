<?php

namespace App\Services\Media;

use App\Enums\MediaCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * [R2 저장 경로 생성 전담 클래스]
 * => 파일을 어디에 저장할 것인가를 결정
 * (실제 업로드 | DB 저장 | 권한 확인 | 이미지 리사이징/변환 X)
 * - 경로 규칙이 여러 곳에 흩어지는 것 막기
 * - 도메인별 경로를 한곳에 관리
 * - 나중에 경로 정책이 바뀌어도 업로드 서비스나 컨트롤러를 많이 수정하지 않기 위해
 * - 테스트에서 이 입력이면 어떤 path가 나오는지 독립적으로 검증
 */
class MediaPathGenerator
{
    /**
     * 업로드 파일의 R2 경로 생성
     *
     * @param UploadedFile $file 사용자가 업로드한 파일 객체
     * @param MediaCollection $collection 이미지 사용 용도
     * @param Model|null $owner 파일이 연결될 도메인 모델 ex) 프로필 이미지: User
     *
     * @return string R2 bucket 내부의 상대 경로(path만 반환)
     */
    public function generate(
        UploadedFile $file,
        MediaCollection $collection,
        ?Model $owner = null,
    ): string {
        // 원본 파일명은 노출/충돌 위험이 있으므로 ULID 기반 파일명을 사용한다.
        $filename = Str::ulid().'.'.$this->extension($file);

        // 현재는 공통 기반 범위만 다룬다. 본문/댓글/신고 이미지는 해당 기능 구현 시 추가한다.
        return match ($collection) {
            MediaCollection::ProfileAvatar => $this->profileAvatarPath($owner, $filename),
            MediaCollection::TipThumbnail => $this->ownedOrTemporaryPath('tips', 'thumbnail', $owner, $filename),
            MediaCollection::QuestionThumbnail => $this->ownedOrTemporaryPath('questions', 'thumbnail', $owner, $filename),
        };
    }

    /**
     * 업로드 파일의 확장자 결정
     *
     * - Laravel/Symfony가 MIME 정보를 기반으로 추정한 확장자를 반환.
     */
    private function extension(UploadedFile $file): string
    {
        return $file->extension() ?: 'bin';
    }

    /**
     * 프로필 이미지 저장 경로
     *
     * ex) profiles/15/avatar/01JZABC.webp
     */
    private function profileAvatarPath(?Model $owner, string $filename): string
    {
        $ownerKey = $this->requiredOwnerKey($owner, MediaCollection::ProfileAvatar);

        return "profiles/{$ownerKey}/avatar/{$filename}";
    }

    /**
     * owner가 있으면 도메인 모델 기준 경로를 만들고,
     * owner가 없으면 임시 저장 경로를 만든다.
     *
     * ex)
     * - tips/42/thumbnail/01JZABC.webp
     * - media/temporary/tips/thumbnail/01JZABC.webp
     */
    private function ownedOrTemporaryPath(
        string $resource,
        string $directory,
        ?Model $owner,
        string $filename,
    ): string {
        if ($owner === null) {
            return "media/temporary/{$resource}/{$directory}/{$filename}";
        }

        return "{$resource}/{$owner->getKey()}/{$directory}/{$filename}";
    }

    /**
     * owner가 필수인 collection에서 owner key를 가져온다.
     *
     * 프로필 이미지처럼 특정 모델 없이 저장되면 안 되는 경우에 사용한다.
     * owner가 없으면 잘못된 호출이므로 예외를 발생시킨다.
     */
    private function requiredOwnerKey(?Model $owner, MediaCollection $collection): string|int
    {
        if ($owner === null) {
            throw new \InvalidArgumentException(
                "The [{$collection->value}] media collection requires an owner model."
            );
        }

        return $owner->getKey();
    }
}
