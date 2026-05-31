<?php

namespace App\Data\Answers;

readonly class NumberAnswerData
{
    public function __construct(
        public int|float $value,
    ) {}

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): self
    {
        return new self(value: $data['value']);
    }
}
