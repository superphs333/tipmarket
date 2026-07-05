{{--
    이 폼은 콘솔과 프론트의 팁 작성/수정 화면에서 함께 쓸 수 있는 공유 UI다.
    저장 URL과 취소 URL을 내부에서 console.* 라우트로 고정하지 않고,
    부모 화면이 action/method/cancelUrl을 넘겨 현재 화면의 목적지를 결정한다.
--}}
<div class="tip-edit-shell">
    <form
        method="POST"
        action="{{ $action }}"
        enctype="multipart/form-data"
    >
        @csrf

        {{--
            브라우저 form은 PUT/PATCH/DELETE를 직접 전송하지 못한다.
            수정 화면처럼 POST가 아닌 의미의 요청은 Laravel의 hidden _method 값으로 전달한다.
        --}}
        @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
            @method($method)
        @endif
        <div class="tip-edit-layout">
            <section class="tip-edit-main">
                <div class="tip-edit-field">
                    <label for="tip-title" class="tip-edit-label">
                        제목
                    </label>
                    <input
                        id="tip-title"
                        type="text"
                        name="title"
                        value="{{ old('title', $tip->title) }}"
                        maxlength="160"
                        class="tip-edit-control"
                    >
                </div>

                <div class="tip-edit-field">
                    <label for="tip-content" class="tip-edit-label">
                        본문
                    </label>
                    <x-editors.summernote
                        id="tip-content"
                        name="content"
                        :value="$tip->content"
                        placeholder="팁 본문을 입력하세요."
                    />
                </div>
            </section>

            <aside class="tip-edit-sidebar">
                <section
                    class="tip-edit-panel"
                    x-data="{
                        previewUrl: null,
                        setPreview(event) {
                            const file = event.target.files[0] ?? null;

                            if (this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                            }

                            if (!file || !file.type.startsWith('image/')) {
                                return;
                            }

                            this.previewUrl = URL.createObjectURL(file);
                        },
                        clearPreview() {
                            if (this.previewUrl) {
                                URL.revokeObjectURL(this.previewUrl);
                                this.previewUrl = null;
                            }

                            this.$refs.thumbnailInput.value = '';
                        },
                    }"
                >
                    <div class="tip-edit-panel-title">썸네일</div>

                    <div class="tip-edit-thumbnail">
                        <img
                            x-show="previewUrl"
                            x-bind:src="previewUrl"
                            alt="선택한 썸네일 미리보기"
                            class="tip-edit-thumbnail-image"
                        >
                        <span x-show="!previewUrl">
                            등록된 썸네일 없음
                        </span>
                    </div>

                    <div class="tip-edit-thumbnail-actions">
                        <input
                            x-ref="thumbnailInput"
                            x-on:change="setPreview"
                            type="file"
                            name="thumbnail"
                            accept="image/png,image/jpeg,image/webp"
                            class="tip-edit-file"
                        >

                        <button
                            type="button"
                            class="tip-edit-ghost-action"
                            x-on:click="clearPreview"
                        >
                            썸네일 삭제
                        </button>

                        <p class="tip-edit-help">
                            권장 비율 1.91:1, jpg/png/webp 이미지를 사용합니다.
                        </p>
                    </div>
                </section>

                <section class="tip-edit-panel">
                    <div class="tip-edit-field">
                        <label for="tip-category" class="tip-edit-label">
                            카테고리
                        </label>
                        <select
                            id="tip-category"
                            name="category_id"
                            class="tip-edit-control"
                        >
                            <option value="">선택 안 함</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $tip->category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <x-tags.selector
                        label="태그"
                        name="tag_names"
                        :max-count="20"
                        :selected="$tip->tags"
                    />
                </section>

                <section class="tip-edit-panel">
                    <div class="tip-edit-field">
                        <label for="tip-status" class="tip-edit-label">
                            상태
                        </label>
                        <select
                            id="tip-status"
                            name="status"
                            class="tip-edit-control"
                        >
                            <option value="draft" @selected(old('status', $tip->status) === 'draft')>임시저장</option>
                            <option value="published" @selected(old('status', $tip->status) === 'published')>발행</option>
                        </select>
                    </div>

                    <div class="tip-edit-field">
                        <label for="tip-audience" class="tip-edit-label">
                            노출
                        </label>
                        <select
                            id="tip-audience"
                            name="audience"
                            class="tip-edit-control"
                        >
                            <option value="public" @selected(old('audience', $tip->audience) === 'public')>전체공개</option>
                            <option value="premium" @selected(old('audience', $tip->audience) === 'premium')>프리미엄</option>
                            <option value="private" @selected(old('audience', $tip->audience) === 'private')>비공개</option>
                        </select>
                    </div>

                    <label class="tip-edit-check">
                        <input
                            type="checkbox"
                            name="allow_comments"
                            value="1"
                            class="tip-edit-checkbox"
                            @checked(old('allow_comments', $tip->allow_comments))
                        >
                        댓글 허용
                    </label>
                </section>
            </aside>
        </div>

        <div class="tip-edit-actions">
            <a
                href="{{ $cancelUrl }}"
                wire:navigate
                class="tip-edit-button tip-edit-button-secondary"
            >
                취소
            </a>
            <button
                type="submit"
                class="tip-edit-button tip-edit-button-primary"
            >
                {{ $mode === 'create' ? '등록 저장' : '수정 저장' }}
            </button>
        </div>
    </form>
</div>
