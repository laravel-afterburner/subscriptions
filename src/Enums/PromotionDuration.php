<?php

namespace Afterburner\Subscriptions\Enums;

enum PromotionDuration: string
{
    case Once = 'once';
    case Repeating = 'repeating';
    case Forever = 'forever';
}
