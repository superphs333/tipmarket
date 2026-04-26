<?php

namespace App\Services\Media;

use InvalidArgumentException;

final class MediaPath
{
    private const ROOT = 'media';

    public static function userProfile(int|string $userId): string
    {
        return self::build('users', (string) $userId, 'profile');
    }

    public static function postEditor(int|string $postId): string
    {
        return self::build('posts', (string) $postId, 'editor');
    }

    public static function postThumbnails(int|string $postId): string
    {
        return self::build('posts', (string) $postId, 'thumbnails');
    }

    /**
     * 저장 prefix를 한 곳에서 조합해 path schema가 흔들리지 않게 한다.
     */
    private static function build(string ...$segments): string
    {
        $normalizedSegments = array_map(
            static function (string $segment): string {
                $segment = trim($segment, '/');

                if ($segment === '') {
                    throw new InvalidArgumentException('미디어 경로 segment는 비어 있을 수 없습니다.');
                }

                return $segment;
            },
            $segments,
        );

        array_unshift($normalizedSegments, self::ROOT);

        return implode('/', $normalizedSegments);
    }
}
