<?php

use App\Livewire\Console\Tips\AiCreateTip;
use App\Models\Tip;
use App\Models\User;
use App\Services\Ai\Tip\GenerateTipsFromPrompt;
use Illuminate\Support\Facades\Http;
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

test('ai tip creation shows a ui error when the ai request fails', function () {
    config()->set('services.openai.key', 'test-key');
    config()->set('services.openai.tip_model', 'test-model');
    config()->set('services.openai.tip_timeout', 5);
    config()->set('services.openai.responses_endpoint', 'https://api.openai.test/v1/responses');

    Http::fake([
        'api.openai.test/*' => Http::response(['error' => 'temporary unavailable'], 500),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(AiCreateTip::class)
        ->set('prompt', '욕실 청소 팁을 작성해줘.')
        ->call('generate')
        ->assertHasErrors([
            'aiGeneration' => 'AI 생성에 실패했습니다. 잠시 후 다시 시도해 주세요.',
        ])
        ->assertSee('AI 생성에 실패했습니다. 잠시 후 다시 시도해 주세요.');

    expect(Tip::query()->count())->toBe(0);
});
