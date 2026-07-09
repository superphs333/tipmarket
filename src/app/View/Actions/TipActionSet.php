<?php

/**
 * [팁 화면에서 사용할 액션 정의 배열을 만드는 클래스]
 * 
 * => 좋아요, 북마크, 댓글, 공유 같은 액션을 
 * blade 컴포넌트가 이해할 수 있는 배열 구조로 변환 
 * 
 * 역할]
 * - 화면별로 필요한 액션 목록을 구성
 * - 각 액션의 label, count, active, visible, herf/action 값을 정함
 * - blade 안에 긴 액셔 ㄴ배열이 흩어지지 않게 함. 
 */
namespace App\View\Actions;

use App\Models\Tip;

class TipActionSet
{
    /**
     * 팁 상세 화면의 반응 액션 구성을 반환한다.
     *
     * 
     * @param  Tip  $tip  액션 count를 가져올 팁 모델
     * @param  bool  $bookmarkedByViewer  현재 사용자가 이 팁을 북마크했는지 여부
     * @param  bool  $likedByViewer  현재 사용자가 이 팁을 좋아요했는지 여부
     * @return array<int, array<string, mixed>>
     * @return array<int, array<string, mixed>>
     */
    public static function show(
        Tip $tip,
        bool $bookmarkedByViewer = false,
        bool $likedByViewer = false,
    ): array {
        return [
            [
                'name' => 'bookmark',
                'label' => '북마크',
                'count' => $tip->bookmark_count,
                'active' => $bookmarkedByViewer,
                'visible' => true,
                'type' => 'button',
            ],
            [
                'name' => 'like',
                'label' => '좋아요',
                'count' => $tip->like_count,
                'active' => $likedByViewer,
                'visible' => true,
                'type' => 'button',
            ],
            [
                'name' => 'comment',
                'label' => '댓글',
                'count' => $tip->comment_count,
                'visible' => false,
                'href' => '#tip-comments',
            ],
            [
                'name' => 'share',
                'label' => '공유',
                'count' => null,
                'visible' => true,
                'type' => 'button',
            ],
        ];
    }
}
