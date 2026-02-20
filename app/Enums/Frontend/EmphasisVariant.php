<?php

namespace App\Enums\Frontend;

enum EmphasisVariant: string
{
    case NEUTRAL = 'neutral';
    case AFFIRMATIVE = 'affirmative';
    case INFORMATIVE = 'informative';
    case PREVENTIVE = 'preventive';
    case DESTRUCTIVE = 'destructive';
    case INTERROGATIVE = 'interrogative';
}
