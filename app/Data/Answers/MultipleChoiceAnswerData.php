<?php

namespace App\Data\Answers;

readonly class MultipleChoiceAnswerData
{
    /**
     * @param int[] $option_ids
     */
    public function __construct(
        public array $option_ids,
    ) {}

    public function toArray(): array
    {
        return ['option_ids' => $this->option_ids];
    }

    public static function fromArray(array $data): self
    {
        return new self(option_ids: array_map('intval', $data['option_ids']));
    }
}
