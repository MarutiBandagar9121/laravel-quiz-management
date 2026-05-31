<?php

namespace App\Enums;

enum QuestionStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
}
