@props(['tip'])
@php
    $author = $tip->user;
    $avatarUrl = $author?->profileAvatar?->publicUrl();
@endphp
<header class="tip-show__article-header">
    <div class="tip-show__header-top">
        @if ($tip->category)
            <a href="#" class="tip-show__category-chip">
                {{ $tip->category->name }}
            </a>
        @endif


        <div class="tip-show__badge-row">
            <span class="tip-show__badge">
                {{ strtoupper($tip->status) }}
            </span>
            <span class="tip-show__badge">
                {{ strtoupper($tip->audience) }}
            </span>
        </div>
    </div>

    <h1 class="tip-show__title">
        {{ $tip->title }}
    </h1>

    <div class="tip-show__author">
        @if ($avatarUrl)
            <img
                class="tip-show__avatar"
                src="{{ $avatarUrl }}"
                alt="{{ $author?->name ?? '작성자' }} 프로필"
                loading="lazy"
            >
        @else
            <span class="tip-show__avatar" aria-hidden="true"></span>
        @endif
        <span>{{ $tip->user?->name ?? '작성자' }}</span>
    </div>

    <div class="tip-show__meta-row">
        <p class="tip-show__meta">
            <span>수정일 {{ $tip->updated_at?->format('Y.m.d H:i') }}</span>
            <span>조회 {{ number_format($tip->view_count) }}</span>
        </p>

        @if (auth()->check() && (auth()->user()->can('update', $tip) || auth()->user()->can('delete', $tip)))
            <div class="tip-show__owner-actions" aria-label="팁 관리">
                @can('update', $tip)
                    <a href="{{ route('tips.edit', $tip) }}" class="tip-show__owner-action">
                        수정
                    </a>
                @endcan

                @can('delete', $tip)
                    <form
                        action="{{ route('tips.destroy', $tip) }}"
                        method="post"
                        onsubmit="return confirm('이 팁을 삭제하시겠습니까?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="tip-show__owner-action tip-show__owner-action--danger" aria-label="팁 삭제">
                            삭제
                        </button>
                    </form>
                @endcan
            </div>
        @endif
    </div>
</header>
