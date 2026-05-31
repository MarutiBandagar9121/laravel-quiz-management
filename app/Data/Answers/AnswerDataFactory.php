<?php

namespace App\Data\Answers;

use InvalidArgumentException;

class AnswerDataFactory
{
    public static function fromArray(string $questionType, array $data): BinaryAnswerData|SingleChoiceAnswerData|MultipleChoiceAnswerData|NumberAnswerData|TextAnswerData
    {
        return match ($questionType) {
            'binary'          => BinaryAnswerData::fromArray($data),
            'single_choice'   => SingleChoiceAnswerData::fromArray($data),
            'multiple_choice' => MultipleChoiceAnswerData::fromArray($data),
            'number_input'    => NumberAnswerData::fromArray($data),
            'text_input'      => TextAnswerData::fromArray($data),
            default           => throw new InvalidArgumentException("Unknown question type: {$questionType}"),
        };
    }
}
