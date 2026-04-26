<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cloudflare R2에 이미지 파일을 저장/삭제/조회하기 위한 전용 서비스 
 */
class R2ImageStorageService
{
    /**
     * Cloudflare R2에 연결된 Laravel filesystem disk 이름.
     */
    private const DISK = 'r2';

    /**
     * 허용할 이미지 MIME 타입과 저장 확장자 매핑.
     */
    private const IMAGE_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
    ];

    /**
     * 사용자가 직접 업로드한 이미지를 R2에 저장한다.
     *
     * $prefix:
     * - MediaPath::userProfile(1) => media/users/1/profile
     * - MediaPath::postEditor(10) => media/posts/10/editor
     * - MediaPath::postThumbnails(10) => media/posts/10/thumbnails
     *
     * $filename:
     * - null 이면 UUID 파일명 사용
     * - 값이 있으면 path-safe base name으로 정리한 뒤 UUID를 붙여 저장
     *
     * 반환값은 공개 URL이 아니라 R2 key(path)이다.
     */
    public function store(UploadedFile $file, string $prefix, ?string $filename = null): string
    {
        $prefix = $this->normalizePrefix($prefix);

        $mimeType = Str::lower($file->getMimeType() ?? '');
        $extension = $this->extensionFromMime($mimeType);

        if ($extension === null) {
            throw new InvalidArgumentException('지원하지 않는 이미지 형식입니다.');
        }

        $path = $this->buildPath($prefix, $extension, $filename);

        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw new RuntimeException('업로드 파일을 읽을 수 없습니다.');
        }

        try {
            $stored = Storage::disk(self::DISK)->put($path, $stream, [
                'visibility' => 'public',
                'ContentType' => $mimeType,
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $stored) {
            throw new RuntimeException('R2 이미지 업로드에 실패했습니다.');
        }

        return $path;
    }

    /**
     * 외부 URL의 이미지를 내려받아 R2에 저장한다.
     *
     * 주로 소셜 프로필 이미지 흡수에 사용한다.
     * 외부 네트워크는 실패 가능성이 높으므로 null 반환으로 처리한다.
     */
    public function storeFromUrl(string $url, string $prefix, ?string $filename = null): ?string
    {
        $prefix = $this->normalizePrefix($prefix);

        if (blank($url)) {
            return null;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(10)
                ->accept('image/*')
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $mimeType = Str::before(
            Str::lower($response->header('Content-Type') ?? ''),
            ';',
        );

        $extension = $this->extensionFromMime($mimeType);

        if ($extension === null) {
            return null;
        }

        $path = $this->buildPath($prefix, $extension, $filename);

        $stored = Storage::disk(self::DISK)->put($path, $response->body(), [
            'visibility' => 'public',
            'ContentType' => $mimeType,
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);

        return $stored ? $path : null;
    }

    /**
     * R2에 저장된 파일을 삭제한다.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $path = ltrim($path, '/');
        $disk = Storage::disk(self::DISK);

        if (! $disk->fileExists($path)) {
            return;
        }

        if (! $disk->delete($path)) {
            throw new RuntimeException('R2 이미지 삭제에 실패했습니다.');
        }

        if ($disk->fileExists($path)) {
            throw new RuntimeException('R2 이미지가 실제로 삭제되지 않았습니다.');
        }
    }

    /**
     * 저장된 path를 브라우저에서 접근 가능한 URL로 변환한다.
     */
    public function url(string $path): string
    {
        if (blank($path)) {
            throw new InvalidArgumentException('이미지 경로가 비어 있습니다.');
        }

        return Storage::disk(self::DISK)->url(ltrim($path, '/'));
    }

    /**
     * prefix와 파일명 규칙을 합쳐 최종 path를 만든다.
     *
     * 예:
     * - media/users/1/profile/550e8400-e29b-41d4-a716-446655440000.jpg
     * - media/posts/10/thumbnails/cover-sm-550e8400-e29b-41d4-a716-446655440000.webp
     */
    private function buildPath(string $prefix, string $extension, ?string $filename = null): string
    {
        $uuid = Str::uuid()->toString();
        $name = filled($filename)
            ? sprintf('%s-%s', $this->sanitizeFilename($filename), $uuid)
            : $uuid;

        return sprintf('%s/%s.%s', $prefix, $name, $extension);
    }

    /**
     * prefix 입력을 정리한다.
     */
    private function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix, '/');

        if ($prefix === '') {
            throw new InvalidArgumentException('저장 prefix는 비어 있을 수 없습니다.');
        }

        return $prefix;
    }

    /**
     * 파일명을 path-safe 형태로 정리한다.
     *
     * 확장자는 MIME 타입에서 결정하므로, 입력값에 확장자가 있어도 base name만 사용한다.
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = pathinfo($filename, PATHINFO_FILENAME);

        $filename = Str::of($filename)
            ->trim()
            ->lower()
            ->slug('-')
            ->value();

        if ($filename === '') {
            throw new InvalidArgumentException('파일명은 비어 있을 수 없습니다.');
        }

        return $filename;
    }

    /**
     * MIME 타입으로 저장 확장자를 결정한다.
     */
    private function extensionFromMime(?string $mimeType): ?string
    {
        return self::IMAGE_EXTENSIONS[$mimeType] ?? null;
    }
}
