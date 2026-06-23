<?php

/**
 * 이미지 용도를 문자열로 흩뿌리지 않기 위함.
 */

namespace App\Enums;

enum MediaCollection: string
{
    case ProfileAvatar = 'profile_avatar';
    case TipThumbnail = 'tip_thumbnail';
    case QuestionThumbnail = 'question_thumbnail';
}
