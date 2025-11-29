<?php

return [
    'tax_rate' => env('BUSINESS_TAX_RATE', 0.18),
    'default_markup_rate' => env('BUSINESS_MARKUP_RATE', 0.20),
    'min_markup_rate' => env('BUSINESS_MIN_MARKUP_RATE', 0.10),
    'max_markup_rate' => env('BUSINESS_MAX_MARKUP_RATE', 0.50),
];
