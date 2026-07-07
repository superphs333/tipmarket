<section id="tip-reactions" class="tip-show__section tip-show__reactions">
    <div class="tip-show__reaction-grid">
        <section class="tip-show__reaction-panel" aria-label="좋아요한 사람">
            <div class="tip-show__reaction-head">
                <strong>좋아요한 사람</strong>
                <button type="button" class="tip-show__reaction-count">25</button>
            </div>
            <div class="tip-show__reaction-users">사용자 A · 사용자 B · 사용자 C</div>
        </section>

        <section class="tip-show__reaction-panel" aria-label="북마크한 사람">
            <div class="tip-show__reaction-head">
                <strong>북마크한 사람</strong>
                <button type="button" class="tip-show__reaction-count">13</button>
            </div>
            <div class="tip-show__reaction-users">사용자 D · 사용자 E · 사용자 F</div>
        </section>
    </div>

    <section class="tip-show__reaction-modal" hidden aria-hidden="true">
        <div class="tip-show__reaction-modal-backdrop"></div>
        <div class="tip-show__reaction-modal-dialog" role="dialog" aria-modal="true">
            <header class="tip-show__reaction-modal-head">
                <h2>반응한 사람</h2>
                <button type="button" aria-label="닫기">&times;</button>
            </header>

            <div class="tip-show__reaction-modal-tabs">
                <button type="button" class="is-active">좋아요</button>
                <button type="button">북마크</button>
            </div>

            <div class="tip-show__reaction-modal-body">반응 사용자 목록</div>
        </div>
    </section>
</section>
