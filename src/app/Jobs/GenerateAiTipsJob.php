<?php

namespace App\Jobs;

use App\Actions\Tips\CreateAiGeneratedTips;
use App\Models\AiTipGenerationRequest;
use App\Models\Category;
use App\Services\Ai\Tip\BuildTipGenerationPrompt;
use App\Services\Ai\Tip\GenerateTipsFromPrompt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI 팁 생성 요청을 백그라운드에서 처리
 */
class GenerateAiTipsJob implements ShouldQueue
{
    use Queueable;

    // 재시도 횟수
    public int $tries = 3;
    // queue worker가 허용할 Job 전체 실행 시간
    public int $timeout;
    
    public function __construct(
        public int $generationRequestId,
        public int $requestedCount,
    ) {
        // AI 생성은 요청 개수가 많을수록 오래 걸리므로 1개당 시간을 곱해 계산한다.
        // 최소 60초는 보장하고, 20개 요청이 과도하게 길어지지 않도록 최대 300초로 제한한다.
        $this->timeout = min(
            max(60, $this->requestedCount * 15),
            300,
        );
    }


    /**
     * queue worker가 실행하는 실제 AI 팁 생성 작업.
     */
    public function handle(
        BuildTipGenerationPrompt $buildPrompt,
        GenerateTipsFromPrompt $generateTips,
        CreateAiGeneratedTips $createTips,
    ): void
    {
        // Job에는 모델 전체가 아니라 id만 저장해두고, 실행 시점의 최신 상태를 다시 조회
        $generationRequest = AiTipGenerationRequest::query()
            ->with('user')
            ->findOrFail($this->generationRequestId);
        
        // 재시도 또는 중복 실행 상황에서 이미 완료된 요청이면 다시 Tip을 만들지 않음. 
        if($generationRequest->status === 'completed') return;

        // 사용자가 화면에서 진행 상태를 확인할 수 있도록 실제 처리 시작 시점을 기록
        $generationRequest->update([
            'status' => 'processing',
            'started_at' => $generationRequest->started_at ?? now(),
            'error_message' => null,
        ]);

        // 요청 당시 저장한 category_id를 현재 카테고리 이름으로 변환해 프롬프트에 반영
        $categoryName = Category::query()
            ->whereKey($generationRequest->category_id)
            ->value('name');
        
        $tagNames = $generationRequest->tag_names ?? [];

        // 프롬프트 작성
        $prompt = $buildPrompt(
            prompt : $generationRequest->prompt ?? '',
            count : $generationRequest->requested_count,
            categoryName : $categoryName,
            tagNames : $tagNames,
        );

        // OpenAI 호출과 응답 파싱
        $drafts = $generateTips(
            prompt : $prompt,
            categoryId:$generationRequest->category_id,
            requiredTagNames:$tagNames,
            requestedCount: $generationRequest->requested_count,
        );

        // Tip 저장
        $tips = $createTips(
            author : $generationRequest->user,
            drafts : $drafts,
        );

        // 실제 저장된 개수를 기준으로 완료 상태를 기록
        $generationRequest->update([
            'status' => 'completed',
            'created_count' => count($tips),
            'failed_count' => max(0, $generationRequest->requested_count - count($tips)),
            'completed_at' => now(),
        ]);
    }

    /**
     * 라라벨에 모든 재시도를 끝낸 뒤 호출하는 최종 실패 처리
     */
    public function failed(Throwable $exception) : void
    {
        $generationRequest = AiTipGenerationRequest::query()
            ->find($this->generationRequestId);
        
        if($generationRequest === null){
            Log::warning('AI tip generation job failed without request record',[
                'request_id' => $this->generationRequestId,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            return;
        }

        // 사용자 화면에는 내부 예외 내용을 그대로 노출하지 않고 안전한 안내 문구만 저장
        $generationRequest->update([
            'status' => 'failed',
            'failed_count' => $generationRequest->requested_count,
            'error_message' => 'AI 팁 생성에 실패했습니다. 잠시 후 다시 시도해 주세요.',
            'completed_at' => now()
        ]);

        // 개발자 로그 : 추적 가능한 최소 정보만 남김
        Log::warning('AI tip generation job failed.', [
            'request_id' => $generationRequest->id,
            'user_id' => $generationRequest->user_id,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'requested_count' => $generationRequest->requested_count,
            'created_count' => $generationRequest->created_count,
            'model' => config('services.openai.tip_model'),
            'timeout' => $this->timeout,
        ]);
        
    }
}
