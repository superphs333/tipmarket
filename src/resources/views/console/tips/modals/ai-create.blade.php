<flux:modal name="ai-tip-create" focusable class="max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] max-w-6xl overflow-y-auto sm:w-[calc(100vw-4rem)]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">AI로 팁 추가</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                카테고리와 태그는 선택 사항입니다.
            </flux:text>
        </div>

        <div class="space-y-5">
            <div class="space-y-2">
                <flux:select label="카테고리">
                    <flux:select.option value="">카테고리 미선택</flux:select.option>
                    @foreach($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- 태그 선택 --}}
            <x-tags.selector />

            <flux:textarea
                label="요청 내용"
                rows="5"
                placeholder="어떤 팁을 만들고 싶은지 입력하세요. 예: 전세 계약 전에 꼭 확인할 항목을 정리해줘."
            />
        </div>

        <div class="flex flex-col gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-700 sm:flex-row sm:items-end sm:justify-between">
            <flux:input
                label="생성 개수"
                type="number"
                min="1"
                max="20"
                value="1"
                class="sm:max-w-40"
            />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="filled" class="w-full sm:w-auto">
                        취소
                    </flux:button>
                </flux:modal.close>

                <flux:button
                    type="button"
                    variant="primary"
                    icon="sparkles"
                    class="w-full sm:w-auto"
                >
                    AI 생성
                </flux:button>
            </div>
        </div>
    </div>
</flux:modal>
