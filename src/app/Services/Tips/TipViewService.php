<?php

namespace App\Services\Tips;

use App\Models\Tip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * 팁 상세 페이지 조회수를 기록하는 서비스
 */
class TipViewService
{
    private const VIEW_TTL_SECONDS = 86400;

    /**
     * 팁 상세 조회 1회를 기록
     *
     * @param  Tip  $tip  상세 화면에 표시할 팁 모델
     * @param  Request  $request  viewer 식별에 사용할 현재 HTTP 요청
     * @return bool 이번 요청으로 DB view_count가 증가했으면 true, 중복 조회면 false
     */
    public function record(Tip $tip, Request $request): bool
    {
        if (! $tip->isPublished()) {
            return false;
        }

        $key = $this->viewerKey($tip, $request);

        // 키가 없을 때만 저장
        $recorded = Redis::connection()->set(
            $key,
            '1',
            'EX',
            self::VIEW_TTL_SECONDS,
            'NX',
        );

        if ($recorded !== true && $recorded !== 'OK') {
            return false;
        }
        $tip->increment('view_count');

        return true;
    }

    /**
     * 현재 요청의 viewr를 Redis 중복 방지 키로 변환
     *
     * @param  Tip  $tip  조회 대상 팁
     * @param  Request  $request  viewer 식별에 사용할 현재 HTTP 요청
     * @return string Redis에 저장할 중복 방지 키
     */
    private function viewerKey(Tip $tip, Request $request): string
    {
        $user = $request->user();

        if ($user !== null) {
            return "tipmarket:tips:{$tip->id}:views:user:{$user->id}";
        }

        return "tipmarket:tips:{$tip->id}:views:guest:".$this->guestFingerprint($request);
    }

    /**
     * 비로그인 viewer를 식별하기 위한 fingerprint 생성
     *
     * @param  Request  $request  비로그인 viewer의 요청 정보
     * @return string Redis key에 포함할 고정 길이 해시
     */
    private function guestFingerprint(Request $request): string
    {
        return sha1($request->ip().'|'.(string) $request->userAgent());
    }
}
