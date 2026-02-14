<?php

namespace App\Support;

class Pricing
{
    public static function applyMarkup(float $cost): float
    {
        $percent = (float) config('pricing.markup_percent', 25);

        return $cost * (1 + ($percent / 100));
    }
}
