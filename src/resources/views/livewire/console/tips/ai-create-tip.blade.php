<flux:modal name="ai-tip-create" focusable class="max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] max-w-6xl overflow-y-auto sm:w-[calc(100vw-4rem)]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">AI로 팁 추가</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                카테고리, 태그, 요청 내용은 선택 사항입니다.
            </flux:text>
        </div>

        <div class="space-y-5">
            @error('aiGeneration')
                <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
            @enderror

            <div class="space-y-2">
                <flux:select label="카테고리" wire:model="categoryId">
                    <flux:select.option value="">카테고리 미선택</flux:select.option>
                    @foreach($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- 태그 선택 --}}
            <livewire:tags.tag-selector
                :key="'ai-tip-tag-selector-'.$tagSelectorKey"
                wire:model="tagNames"
            />

            <flux:textarea
                label="요청 내용"
                rows="5"
                placeholder="비워두면 선택한 카테고리와 태그를 바탕으로 AI가 주제를 정합니다."
                wire:model="prompt"
            />
        </div>

        <div class="flex flex-col gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-700 sm:flex-row sm:items-end sm:justify-between">
            <flux:input
                label="생성 개수"
                type="number"
                min="1"
                max="20"
                class="sm:max-w-40"
                wire:model="count"
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
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    wire:target="generate"
                >
                    AI 생성
                </flux:button>
            </div>
        </div>
    </div>
</flux:modal>
