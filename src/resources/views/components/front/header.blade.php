<header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold" wire:navigate>
            <x-app-logo-icon class="size-8 fill-current text-zinc-900 dark:text-white" />
            <span>{{ config('app.name', 'TipMarket') }}</span>
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium text-zinc-600 md:flex dark:text-zinc-300">
            <a href="{{ route('home') }}" class="transition hover:text-zinc-950 dark:hover:text-white" wire:navigate>
                홈
            </a>
            <a href="#" class="transition hover:text-zinc-950 dark:hover:text-white">
                팁
            </a>
            <a href="#" class="transition hover:text-zinc-950 dark:hover:text-white">
                질문
            </a>
        </nav>

        <div class="flex items-center gap-2 text-sm font-medium">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white" wire:navigate>
                    마이페이지
                </a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-zinc-700 transition hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-white" wire:navigate>
                        로그인
                    </a>
                @endif

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200" wire:navigate>
                        회원가입
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>
