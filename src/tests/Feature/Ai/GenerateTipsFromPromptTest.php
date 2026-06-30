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

test('ai tip creation closes modal and refreshes the tips page after success', function () {
    config()->set('services.openai.key', 'test-key');
    config()->set('services.openai.tip_model', 'test-model');
    config()->set('services.openai.tip_timeout', 5);
    config()->set('services.openai.responses_endpoint', 'https://api.openai.test/v1/responses');

    Http::fake([
        'api.openai.test/*' => Http::response([
            'output_text' => json_encode([
                'tips' => [
                    [
                        'title' => '싱크대 배수구 냄새 줄이는 방법',
                        'summary' => '싱크대 배수구 냄새를 줄이는 관리 팁입니다.',
                        'content' => '<p>뜨거운 물과 베이킹소다로 주기적으로 세척합니다.</p>',
                        'tags' => ['주방관리'],
                    ],
                    [
                        'title' => '분리수거 전 라벨 제거 팁',
                        'summary' => '라벨을 쉽게 제거하는 생활 팁입니다.',
                        'content' => '<p>따뜻한 물에 잠시 불린 뒤 라벨을 떼어냅니다.</p>',
                        'tags' => ['분리수거'],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(AiCreateTip::class)
        ->set('count', 2)
        ->call('generate')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show', function (string $event, array $params): bool {
            return $params['slots']['text'] === '2개가 생성되었습니다.'
                && $params['dataset']['variant'] === 'success';
        })
        ->assertDispatched('modal-close', function (string $event, array $params): bool {
            return $params['name'] === 'ai-tip-create';
        })
        ->assertRedirectToRoute('console.tips.index');

    expect(Tip::query()->count())->toBe(2);
});
