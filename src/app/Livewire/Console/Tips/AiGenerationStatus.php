<?php

namespace App\Livewire\Console\Tips;

use App\Models\AiTipGenerationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * 팁 관리 화면 상단에서 최근 AI 생성 요청의 진행 상태를 표시한다.
 *
 * DB에 저장된 AiTipGenerationRequest 상태를 읽어,
 * 사용자가 확인해야 할 완료/실패 결과만 화면에 보여줌
 *
 * [표시정책]
 * - pending/processing => 카드로 보여주지 x
 * - completed / failed => 요청 1건당 카드 1개
 * - wire:poll을 유지해 백그라운드 작업 완료 여부 주기적으로 확인
 * - 사용자가 x버튼을 누르면 dismissed_at을 기록해 해당 카드를 숨김.
 */
class AiGenerationStatus extends Component
{
    public function render(): View
    {
        $generationRequests = $this->completedGenerationRequests();

        return view('livewire.console.tips.ai-generation-status', [
            'generationRequests' => $generationRequests,
            'shouldPoll' => $this->hasActiveGenerationRequest(),
        ]);
    }

    /**
     * 사용자가 확인한 AI 생성 결과 카드를 숨긴다.
     */
    public function dismiss(int $generationRequestId): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        AiTipGenerationRequest::query()
            ->whereKey($generationRequestId)
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'failed'])
            ->update(['dismissed_at' => now()]);
    }

    /**
     * 현재 사용자가 아직 확인하지 않은 완료/실패 AI 생성 요청 목록 가져옴.
     *
     * @return Collection<int, AiTipGenerationRequest>
     */
    private function completedGenerationRequests(): Collection
    {
        $userId = Auth::id();

        if ($userId === null) {
            return collect();
        }

        return AiTipGenerationRequest::query()
            ->where('user_id', $userId)
            ->whereNull('dismissed_at')
            ->whereIn('status', ['completed', 'failed'])
            ->latest('id')
            ->get();
    }

    /**
     * 아직 처리 중인 AI 요청이 있는지 확인
     * => 완료 여부를 감지하려면 Livewire polling이 계속 돌아야 한다.
     * , 이 값이 true이면 Blade에서 wire:poll.5s를 활성화
     */
    private function hasActiveGenerationRequest(): bool
    {
        $userId = Auth::id();

        if ($userId === null) {
            return false;
        }

        return AiTipGenerationRequest::query()
            ->where('user_id', $userId)
            ->whereNull('dismissed_at')
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
    }

    /**
     * AI 생성 요청 1건의 결과 문구를 만듦
     */
    public function messageFor(AiTipGenerationRequest $generationRequest): string
    {
        return "AI 팁 총 {$generationRequest->requested_count}개 중 " // 사용자가 요청한 총 개수
            ."{$generationRequest->created_count}개 성공, " // 실제 저장된 팁 수
            ."{$generationRequest->failed_count}개 실패"; // 요청 수와 생성 수의 차이 또는 최종 실패 수
    }

    /**
     * 생성 결과 상태에 맞는 알림 카드 색상을 반환
     *
     * - 전체 실패 : danger 색상
     * - 일부 실패 : warning 색상
     * - 전체 성공 : success 색상
     */
    public function statusClassesFor(AiTipGenerationRequest $generationRequest): string
    {
        return match (true) {
            $generationRequest->status === 'failed' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100',
            $generationRequest->failed_count > 0 => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100',
            $generationRequest->status === 'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100',
            default => '',
        };
    }

    /**
     * 결과 카드에 표시할 완료 시간 문자열을 만듦
     */
    public function metaTextFor(AiTipGenerationRequest $generationRequest): ?string
    {
        return $generationRequest->completed_at?->format('Y-m-d H:i');
    }
}
