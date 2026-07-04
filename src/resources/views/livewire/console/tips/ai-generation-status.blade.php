<div @if ($shouldPoll) wire:poll.5s @endif class="space-y-3">
    @foreach ($generationRequests as $generationRequest)
        <section
            wire:key="ai-generation-status-{{ $generationRequest->id }}"
            class="rounded-lg border px-5 py-4 shadow-xs {{ $this->statusClassesFor($generationRequest) }}"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold">{{ $this->messageFor($generationRequest) }}</div>
                    <div class="mt-1 text-xs opacity-75">
                        요청 #{{ $generationRequest->id }}
                        @if ($this->metaTextFor($generationRequest))
                            <span class="mx-2 opacity-50">|</span>
                            {{ $this->metaTextFor($generationRequest) }}
                        @endif
                    </div>
                </div>

                @if ($generationRequest->status === 'completed')
                    <div class="text-xs font-medium opacity-80">
                        목록을 새로고침하면 생성된 팁을 확인할 수 있습니다.
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="dismiss({{ $generationRequest->id }})"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-sm font-semibold opacity-70 transition hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10"
                    aria-label="AI 생성 상태 닫기"
                >
                    x
                </button>
            </div>
        </section>
    @endforeach
</div>
