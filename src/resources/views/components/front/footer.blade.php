<footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-8 text-sm text-zinc-500 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8 dark:text-zinc-400">
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'TipMarket') }}. All rights reserved.</p>

        <nav class="flex items-center gap-4">
            <a href="#" class="transition hover:text-zinc-900 dark:hover:text-white">
                이용약관
            </a>
            <a href="#" class="transition hover:text-zinc-900 dark:hover:text-white">
                개인정보처리방침
            </a>
        </nav>
    </div>
</footer>
