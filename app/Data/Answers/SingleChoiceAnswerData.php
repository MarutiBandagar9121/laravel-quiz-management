<?php

namespace App\Data\Answers;

readonly class SingleChoiceAnswerData
{
    public function __construct(
        public int $option_id,
    ) {}

    public function toArray(): array
    {
        return ['option_id' => $this->option_id];
    }

    public static function fromArray(array $data): self
    {
        return new self(option_id: (int) $data['option_id']);
    }
}
