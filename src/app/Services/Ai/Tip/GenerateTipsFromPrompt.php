<?php

namespace App\Services\Ai\Tip;

use App\Data\Tips\TipDraftData;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

/**
 * 완성된 AI 프롬프트를 OpenAI Responses API에 전달하고, 응답을 TipDraftData 목록으로 변환한다.
 *
 * 이 클래스는 AI 호출과 응답 변환까지만 담당한다.
 * 프롬프트 생성은 BuildTipGenerationPrompt, DB 저장은 CreateTip/CreateAiGeneratedTips가 담당한다.
 */
final class GenerateTipsFromPrompt
{
    /**
     * 완성된 프롬프트로 AI 팁 초안 목록을 생성한다.
     *
     * @param  array<int, string>  $requiredTagNames
     * @return array<int, TipDraftData>
     */
    public function __invoke(
        string $prompt,
        ?int $categoryId = null,
        array $requiredTagNames = [],
    ): array {
        $response = Http::withToken(config('services.openai.key'))
            ->acceptJson()
            ->timeout((int) config('services.openai.tip_timeout', 30))
            ->post(config('services.openai.responses_endpoint'), [
                'model' => config('services.openai.tip_model'),
                'input' => $prompt,
                'text' => [
                    'format' => $this->responseFormat(),
                ],
            ])
            ->throw()
            ->json();

        return collect($this->extractTips($response))
            ->map(fn (array $tip): TipDraftData => TipDraftData::fromAiPayload(
                payload: $tip,
                categoryId: $categoryId,
            ))
            ->map(fn (TipDraftData $draft): TipDraftData => $this->mergeRequiredTagNames($draft, $requiredTagNames))
            ->values()
            ->all();
    }

    /**
     * AI가 참고 태그를 누락해도 저장 단계에는 반드시 포함되도록 보정한다.
     *
     * 프롬프트에서 "참고 태그는 반드시 포함"이라고 지시하더라도 외부 API 응답은
     * 항상 신뢰할 수 없으므로, 앱 내부에서 한 번 더 병합한다.
     *
     * @param  array<int, string>  $requiredTagNames
     */
    private function mergeRequiredTagNames(TipDraftData $draft, array $requiredTagNames): TipDraftData
    {
        $tagNames = collect($requiredTagNames)
            ->merge($draft->tagNames)
            ->map(fn (string $tagName): string => trim($tagName))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return new TipDraftData(
            title: $draft->title,
            content: $draft->content,
            categoryId: $draft->categoryId,
            tagIds: $draft->tagIds,
            summary: $draft->summary,
            tagNames: $tagNames,
        );
    }

    /**
     * OpenAI Structured Outputs에 전달할 응답 JSON 스키마.
     *
     * BuildTipGenerationPrompt도 같은 구조를 문장으로 안내하지만,
     * API 요청 단계에서 스키마를 함께 넘겨 응답 형태가 흔들릴 가능성을 줄인다.
     *
     * @return array<string, mixed>
     */
    private function responseFormat(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'tip_generation_response',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'tips' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'summary' => ['type' => 'string'],
                                'content' => ['type' => 'string'],
                                'tags' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                            'required' => ['title', 'summary', 'content', 'tags'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['tips'],
                'additionalProperties' => false,
            ],
        ];
    }

    /**
     * Responses API 응답에서 JSON 텍스트를 꺼내 tips 배열로 변환한다.
     *
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function extractTips(array $response): array
    {
        $outputText = $response['output_text']
            ?? data_get($response, 'output.0.content.0.text');

        if (! is_string($outputText) || trim($outputText) === '') {
            throw new RuntimeException('AI tip generation response does not contain output text.');
        }

        try {
            $payload = json_decode($outputText, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('AI tip generation response is not valid JSON.', previous: $exception);
        }

        if (! is_array($payload) || ! isset($payload['tips']) || ! is_array($payload['tips'])) {
            throw new RuntimeException('AI tip generation response does not contain a tips array.');
        }

        return collect($payload['tips'])
            ->map(function (mixed $tip): array {
                if (! is_array($tip)) {
                    throw new RuntimeException('Each AI generated tip must be an object.');
                }

                return $tip;
            })
            ->values()
            ->all();
    }
}
