<?php

namespace App\Data\Answers;

readonly class TextAnswerData
{
    public function __construct(
        public string $value,
        public ?string $model_answer = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'value' => $this->value,
            'model_answer' => $this->model_answer,
        ], fn ($v) => $v !== null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'] ?? '',
            model_answer: $data['model_answer'] ?? null,
        );
    }
}
