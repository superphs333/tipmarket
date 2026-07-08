<?php

use App\Livewire\Console\Tips\AiCreateTip;
use App\Jobs\GenerateAiTipsJob;
use App\Models\AiTipGenerationRequest;
use App\Models\Tip;
use App\Models\User;
use App\Services\Ai\Tip\GenerateTipsFromPrompt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('generated tip drafts include required tag names even when ai omits them', function () {
    config()->set('services.openai.key', 'test-key');
    config()->set('services.openai.tip_model', 'test-model');
    config()->set('services.openai.tip_timeout', 5);
    config()->set('services.openai.responses_endpoint', 'https://api.openai.test/v1/responses');

    Http::fake([
        'api.openai.test/*' => Http::response([
            'output_text' => json_encode([
                'tips' => [
                    [
                        'title' => '욕실 물때 줄이는 청소 루틴',
                        'summary' => '욕실 물때를 줄이는 짧은 청소 루틴입니다.',
                        'content' => '<p>샤워 후 물기를 제거하고 주 1회 세정제를 사용합니다.</p>',
                        'tags' => ['욕실관리'],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]),
    ]);

    $drafts = app(GenerateTipsFromPrompt::class)(
        prompt: '욕실 청소 팁을 작성해줘.',
        categoryId: 2,
        requiredTagNames: ['청소루틴', '욕실정리'],
    );

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]->categoryId)->toBe(2)
        ->and($drafts[0]->tagIds)->toBe([])
        ->and($drafts[0]->tagNames)->toBe(['청소루틴', '욕실정리', '욕실관리']);
});

test('ai tip generation job records a failed request when the ai request fails', function () {
    config()->set('services.openai.key', 'test-key');
    config()->set('services.openai.tip_model', 'test-model');
    config()->set('services.openai.tip_timeout', 5);
    config()->set('services.openai.responses_endpoint', 'https://api.openai.test/v1/responses');

    Http::fake([
        'api.openai.test/*' => Http::response(['error' => 'temporary unavailable'], 500),
    ]);

    $generationRequest = AiTipGenerationRequest::create([
        'user_id' => User::factory()->create()->id,
        'category_id' => null,
        'status' => 'pending',
        'prompt' => '욕실 청소 팁을 작성해줘.',
        'tag_names' => [],
        'requested_count' => 1,
        'created_count' => 0,
        'failed_count' => 0,
    ]);

    $job = new GenerateAiTipsJob(
        generationRequestId: $generationRequest->id,
        requestedCount: $generationRequest->requested_count,
    );

    try {
        app()->call([$job, 'handle']);
    } catch (Throwable $exception) {
        $job->failed($exception);
    }

    $generationRequest->refresh();

    expect(Tip::query()->count())->toBe(0);
    expect($generationRequest->status)->toBe('failed')
        ->and($generationRequest->failed_count)->toBe(1)
        ->and($generationRequest->error_message)->toBe('AI 팁 생성에 실패했습니다. 잠시 후 다시 시도해 주세요.')
        ->and($generationRequest->completed_at)->not()->toBeNull();
});

test('ai tip creation queues a generation request and closes the modal', function () {
    Queue::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(AiCreateTip::class)
        ->set('count', 2)
        ->call('generate')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', function (string $event, array $params): bool {
            return $params['slots']['text'] === 'AI 팁 생성을 시작했습니다. 완료되면 이 화면에서 확인할 수 있습니다.'
                && $params['dataset']['variant'] === 'success';
        })
        ->assertDispatched('modal-close', function (string $event, array $params): bool {
            return $params['name'] === 'ai-tip-create';
        });

    $generationRequest = AiTipGenerationRequest::query()->sole();

    expect($generationRequest->user_id)->toBe($user->id)
        ->and($generationRequest->status)->toBe('pending')
        ->and($generationRequest->requested_count)->toBe(2)
        ->and($generationRequest->created_count)->toBe(0)
        ->and($generationRequest->failed_count)->toBe(0);

    Queue::assertPushed(GenerateAiTipsJob::class, function (GenerateAiTipsJob $job) use ($generationRequest): bool {
        return $job->generationRequestId === $generationRequest->id
            && $job->requestedCount === 2;
    });

    expect(Tip::query()->count())->toBe(0);
});
