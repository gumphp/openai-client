<?php

declare(strict_types=1);

namespace OpenAI\Responses\Chat;

use OpenAI\Contracts\ResponseContract;
use OpenAI\Responses\Concerns\ArrayAccessible;
use OpenAI\Testing\Responses\Concerns\FakeableForStreamedResponse;

/**
 * @implements ResponseContract<array{id: string, object: string, created: int, model: string, choices: array<int, array{index: int, delta: array{role?: string, content?: string}|array{role?: string, content: null, function_call: array{name?: string, arguments?: string}}, finish_reason: string|null}>, usage?: array{prompt_tokens: int, completion_tokens: int|null, total_tokens: int}}>
 */
final class CreateStreamedResponse implements ResponseContract
{
    /**
     * @use ArrayAccessible<array{id: string, object: string, created: int, model: string, choices: array<int, array{index: int, delta: array{role?: string, content?: string}|array{role?: string, content: null, function_call: array{name?: string, arguments?: string}}, finish_reason: string|null}>, usage?: array{prompt_tokens: int, completion_tokens: int|null, total_tokens: int}}>
     */
    use ArrayAccessible;

    use FakeableForStreamedResponse;

    /**
     * @param  array<int, CreateStreamedResponseChoice>  $choices
     */
    private function __construct(
        public readonly string $id,
        public readonly string $object,
        public readonly int $created,
        public readonly string $model,
        public readonly array $choices,
        public readonly ?CreateResponseUsage $usage,
        public readonly ?array $botUsage,
        public readonly ?array $references,
    ) {}

    /**
     * Acts as static factory, and returns a new Response instance.
     *
     * @param  array{id: string, object: string, created: int, model: string, choices: array<int, array{index: int, delta: array{role?: string, content?: string}, finish_reason: string|null}>, usage?: array{prompt_tokens: int, completion_tokens: int|null, total_tokens: int}}  $attributes
     */
    public static function from(array $attributes): self
    {
        $choices = array_map(fn (array $result): CreateStreamedResponseChoice => CreateStreamedResponseChoice::from(
            $result
        ), $attributes['choices']);

        $usage = self::getUsage($attributes);
        return new self(
            $attributes['id'] ?? 'unknown id',
            $attributes['object'] ?? 'unknown object',
            $attributes['created'] ?? time(),
            $attributes['model'] ?? 'unknown model',
            $choices,
            $usage ? CreateResponseUsage::from($usage) : null,
            isset($attributes['bot_usage']) ? $attributes['bot_usage'] : null,
            isset($attributes['references']) ? $attributes['references'] : null,
        );
    }

    protected static function getUsage(array $attributes)
    {
        if (isset($attributes['usage']) && $attributes['usage']) {
            return $attributes['usage'];
        }
        if (isset($attributes['bot_usage']) && $attributes['bot_usage']) {
            return [
                'prompt_tokens' => $attributes['bot_usage']['model_usage'][0]['prompt_tokens'] ?? 0,
                'completion_tokens' => $attributes['bot_usage']['model_usage'][0]['completion_tokens'] ?? 0,
                'total_tokens' => $attributes['bot_usage']['model_usage'][0]['total_tokens'] ?? 0,
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'object' => $this->object,
            'created' => $this->created,
            'model' => $this->model,
            'choices' => array_map(
                static fn (CreateStreamedResponseChoice $result): array => $result->toArray(),
                $this->choices,
            ),
        ];

        if ($this->usage instanceof \OpenAI\Responses\Chat\CreateResponseUsage) {
            $data['usage'] = $this->usage->toArray();
        }

        if ($this->botUsage) {
            $data['bot_usage'] = $this->botUsage;
        }

        if ($this->references) {
            $data['references'] = $this->references;
        }
        return $data;
    }
}
