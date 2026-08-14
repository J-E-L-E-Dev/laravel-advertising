<?php

namespace Ahinest\LaravelAdvertising\Http\Controllers;

use Ahinest\LaravelAdvertising\Services\AdvertisingManager;
use Illuminate\Http\JsonResponse;

class AdvertisementController
{
    public function show(string $advertisement, AdvertisingManager $manager): JsonResponse
    {
        return response()->json(['data' => $manager->resourcesFor($advertisement)]);
    }
}
