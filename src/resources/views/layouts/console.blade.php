<!DOCTYPE html>
{{-- 콘솔 화면 전용 HTML 문서다. lang은 현재 앱 locale을 기준으로 설정하고, 콘솔은 기본 dark 테마를 사용한다. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        {{-- 공통 head partial을 불러온다. meta, font, Vite asset, Flux style 같은 전역 head 설정이 들어간다. --}}
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        {{-- 데스크톱/모바일 공용 콘솔 사이드바다. 콘솔 메뉴만 노출하고 일반 사용자 앱 메뉴와 분리한다. --}}
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            {{-- 사이드바 상단 브랜드 영역이다. 클릭하면 콘솔 대시보드로 이동한다. --}}
            <flux:sidebar.header>
                <flux:sidebar.brand name="TipMarket Console" :href="route('console.dashboard')" wire:navigate>
                    {{-- 브랜드 왼쪽에 표시되는 로고 슬롯이다. 기존 앱 로고 아이콘을 콘솔에도 재사용한다. --}}
                    <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
                        <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
                    </x-slot>
                </flux:sidebar.brand>
                {{-- 모바일 화면에서 사이드바를 접는 버튼이다. 데스크톱에서는 숨긴다. --}}
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            {{-- 콘솔의 주요 운영 메뉴 영역이다. 추후 사용자/콘텐츠/신고/지원 탭을 이곳에 추가한다. --}}
            <flux:sidebar.nav>
                {{-- 운영 메뉴 그룹이다. 현재는 콘솔 대시보드만 연결되어 있다. --}}
                <flux:sidebar.group :heading="__('Operations')" class="grid">
                    <flux:sidebar.item icon="shield-check" :href="route('console.dashboard')" :current="request()->routeIs('console.dashboard')" wire:navigate>
                        {{ __('Console Dashboard') }}
                    </flux:sidebar.item>

                    @can('viewAny', \App\Models\Tip::class)
                        <flux:sidebar.item icon="book-open-text" :href="route('console.tips.index')" :current="request()->routeIs('console.tips.*')" wire:navigate>
                            TIPS
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- 데스크톱 사용자 메뉴다. 콘솔에서는 개인 설정 링크 없이 사용자 식별 정보와 로그아웃만 제공한다. --}}
            <flux:dropdown position="bottom" align="start" class="hidden lg:block">
                {{-- 사이드바 하단에 표시되는 현재 사용자 프로필 버튼이다. --}}
                <flux:sidebar.profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu>
                    {{-- 드롭다운 상단 사용자 정보 영역이다. 운영자가 현재 로그인 계정을 확인할 수 있게 한다. --}}
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                        />
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                        </div>
                    </div>

                    <flux:menu.separator />

                    {{-- 콘솔에서 제공하는 계정 액션이다. 현재는 로그아웃만 유지한다. --}}
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        {{-- 모바일 전용 상단 헤더다. 작은 화면에서는 사이드바 토글과 사용자 메뉴를 헤더에서 제공한다. --}}
        <flux:header class="lg:hidden">
            {{-- 모바일에서 콘솔 사이드바를 여는 버튼이다. --}}
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            {{-- 모바일 사용자 메뉴다. 데스크톱 하단 사용자 메뉴와 같은 역할을 한다. --}}
            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    {{-- 모바일 드롭다운 안의 현재 사용자 정보 영역이다. --}}
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                        />

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                        </div>
                    </div>

                    <flux:menu.separator />

                    {{-- 모바일에서도 콘솔 계정 액션은 로그아웃만 제공한다. --}}
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- 실제 콘솔 페이지 내용이 렌더링되는 영역이다. 각 콘솔 view의 $slot이 이곳에 들어온다. --}}
        <flux:main>
            {{ $slot }}
        </flux:main>

        {{-- Livewire/Flux toast 영역이다. 화면 전환 중에도 유지되도록 persist 처리한다. --}}
        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        {{-- Flux 컴포넌트 동작에 필요한 스크립트를 로드한다. --}}
        @fluxScripts
    </body>
</html>
