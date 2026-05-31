<?php

namespace App\Data\Answers;

readonly class BinaryAnswerData
{
    public function __construct(
        public bool $value,
    ) {}

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): self
    {
        return new self(value: (bool) $data['value']);
    }
}
