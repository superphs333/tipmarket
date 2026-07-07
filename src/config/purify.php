<?php

use App\Support\Purify\TipContentDefinition;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Config
    |--------------------------------------------------------------------------
    |
    | Purify::clean($html)처럼 별도 설정명을 지정하지 않고 호출할 때 사용할
    | 기본 설정 이름입니다. 현재는 아래 configs.default를 기본값으로 사용합니다.
    |
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Config Sets
    |--------------------------------------------------------------------------
    |
    | HTMLPurifier를 어디에 사용할지에 따라 허용할 태그와 속성을 다르게 둘 수 있습니다.
    | 예를 들어 팁 본문, 댓글, 프로필 소개는 허용 범위가 서로 달라질 수 있습니다.
    |
    | 사용 예:
    | Purify::config('tip_content')->clean($html)
    |
    | HTMLPurifier 설정 문서:
    | http://htmlpurifier.org/live/configdoc/plain.html
    |
    */
    'configs' => [
        'default' => [
            // 입력 HTML의 문자 인코딩입니다.
            // Laravel 프로젝트는 일반적으로 UTF-8을 사용하므로 utf-8로 둡니다.
            'Core.Encoding' => 'utf-8',

            // HTMLPurifier가 기준으로 삼을 문서 타입입니다.
            // 패키지 기본값을 유지해 일반적인 HTML 조각을 무난하게 정화합니다.
            'HTML.Doctype' => 'HTML 4.01 Transitional',

            // 기본 정화 설정에서 허용할 태그와 속성입니다.
            // tip_content 같은 용도별 설정을 쓰지 않는 경우에만 이 목록이 적용됩니다.
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,b,u,strong,i,em,s,del,a[href|title],ul,ol,li,p[style],br,span,img[width|height|alt|src],blockquote',

            // 명시적으로 금지할 태그 목록입니다.
            // 빈 문자열이면 HTML.Allowed에 없는 태그가 일반 정화 규칙에 따라 제거됩니다.
            'HTML.ForbiddenElements' => '',

            // 기본 설정에서 허용할 inline CSS 속성입니다.
            // 기존 패키지 기본값을 유지하되, 팁 본문에는 아래 tip_content 설정을 우선 사용합니다.
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',

            // 줄바꿈을 자동으로 p 태그로 바꿀지 여부입니다.
            // 에디터가 이미 문단 태그를 생성하므로 기본값은 false로 둡니다.
            'AutoFormat.AutoParagraph' => false,

            // 비어 있는 태그를 제거할지 여부입니다.
            // 기본 설정은 패키지 기본값을 유지합니다.
            'AutoFormat.RemoveEmpty' => false,
        ],

        'tip_content' => [
            // 팁 본문 입력 HTML의 문자 인코딩입니다.
            // DB, Blade, 에디터 모두 UTF-8 기준으로 동작하므로 utf-8을 사용합니다.
            'Core.Encoding' => 'utf-8',

            // HTMLPurifier가 본문 조각을 해석할 때 기준으로 삼을 문서 타입입니다.
            // 에디터가 만든 HTML 조각을 정화하는 용도라 Transitional 기준을 사용합니다.
            'HTML.Doctype' => 'HTML 4.01 Transitional',

            // 팁 본문에서 허용할 HTML 태그와 속성 목록입니다.
            // 여기에 없는 태그와 속성은 저장 전에 제거됩니다.
            //
            // 허용 의도:
            // - p, br: 문단과 줄바꿈
            // - h2, h3, h4: 본문 소제목
            // - ul, ol, li: 목록
            // - strong, b, em, i, u: 기본 텍스트 강조
            // - blockquote, pre, code: 인용문과 코드 표현
            // - a[href|title|target|rel]: 링크와 링크 보안 보조 속성
            // - img[src|alt|title|width|height|data-media-id]: 본문 이미지, 표시 속성,
            //   그리고 저장 후 본문 이미지 media를 Tip에 연결하기 위한 내부 식별자
            //
            // 의도적으로 script, iframe, style, onclick, onerror 같은 태그/속성은 허용하지 않습니다.
            'HTML.Allowed' => implode(',', [
                'p',
                'br',
                'h2',
                'h3',
                'h4',
                'ul',
                'ol',
                'li',
                'strong',
                'b',
                'em',
                'i',
                'u',
                'blockquote',
                'pre',
                'code',
                'a[href|title|target|rel]',
                'img[src|alt|title|width|height|data-media-id]',
            ]),

            // 명시적으로 금지할 태그 목록입니다.
            // HTML.Allowed에서 허용하지 않는 태그는 제거되므로 여기서는 별도 지정하지 않습니다.
            'HTML.ForbiddenElements' => '',

            // 허용할 inline CSS 속성입니다.
            // 사용자 입력 style은 레이아웃 깨짐과 보안 우회 여지가 있어 팁 본문에서는 허용하지 않습니다.
            'CSS.AllowedProperties' => [],

            // 줄바꿈을 자동으로 p 태그로 감쌀지 여부입니다.
            // Summernote 같은 에디터가 이미 p, br을 생성하므로 자동 문단 생성을 끕니다.
            'AutoFormat.AutoParagraph' => false,

            // 의미 없는 빈 태그를 제거할지 여부입니다.
            // <p></p>, <strong></strong>처럼 내용 없는 태그를 정리해 본문 HTML을 깔끔하게 유지합니다.
            'AutoFormat.RemoveEmpty' => true,

            // 링크 target 속성에서 허용할 값입니다.
            // 새 창 열기 용도의 _blank만 허용합니다.
            'Attr.AllowedFrameTargets' => ['_blank'],

            // iframe 허용 여부입니다.
            // 현재 팁 본문은 영상 임베드 요구가 없으므로 허용하지 않습니다.
            // iframe이 필요해질 때는 허용 도메인과 CSP를 함께 별도로 검토해야 합니다.
            'HTML.SafeIframe' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTMLPurifier Definitions
    |--------------------------------------------------------------------------
    |
    | HTMLPurifier가 이해하는 HTML 정의를 확장하는 클래스입니다.
    | 패키지가 제공하는 HTML5 정의를 사용해 더 현대적인 태그/속성 해석을 보조합니다.
    |
    */
    'definitions' => TipContentDefinition::class,

    /*
    |--------------------------------------------------------------------------
    | HTMLPurifier CSS Definitions
    |--------------------------------------------------------------------------
    |
    | CSS 정의를 확장할 때 사용하는 클래스입니다.
    | 현재 팁 본문에서는 inline CSS를 허용하지 않으므로 별도 확장은 사용하지 않습니다.
    |
    */
    'css-definitions' => null,

    /*
    |--------------------------------------------------------------------------
    | Serializer
    |--------------------------------------------------------------------------
    |
    | HTMLPurifier가 내부 정의 캐시를 저장하는 방식입니다.
    | Laravel 캐시 저장소를 사용하면 컨테이너/배포 환경에서 파일 쓰기 권한 문제를 줄일 수 있습니다.
    |
    */
    'serializer' => [
        // CACHE_STORE 또는 CACHE_DRIVER 값을 우선 사용하고, 없으면 file 캐시를 사용합니다.
        'driver' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),

        // Laravel cache store를 통해 HTMLPurifier 정의 캐시를 저장하는 구현체입니다.
        'cache' => \Stevebauman\Purify\Cache\CacheDefinitionCache::class,
    ],

    // 파일시스템 디스크에 직접 serializer 캐시를 저장하고 싶을 때 사용할 수 있는 대안 설정입니다.
    // 현재는 Laravel cache store 기반 serializer를 사용하므로 비활성화합니다.
    //
    // 'serializer' => [
    //     'disk' => env('FILESYSTEM_DISK', 'local'),
    //     'path' => 'purify',
    //     'cache' => \Stevebauman\Purify\Cache\FilesystemDefinitionCache::class,
    // ],
];
