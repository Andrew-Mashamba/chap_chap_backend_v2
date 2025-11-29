<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function getBusinessConfig()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'tax_rate' => config('business.tax_rate'),
                'default_markup_rate' => config('business.default_markup_rate'),
                'min_markup_rate' => config('business.min_markup_rate'),
                'max_markup_rate' => config('business.max_markup_rate'),
            ]
        ]);
    }
}
