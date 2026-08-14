<?php

namespace Ahinest\LaravelAdvertising\Responses;

use Illuminate\Http\JsonResponse;

/** Crea respuestas homogéneas para aplicación web o API. */
class ResponseFactory
{
    /** Devuelve JSON si $response es api; en otro caso devuelve AdvertisingResponse. */
    public static function make(bool $success, string $message, mixed $data, int $status, string $response): AdvertisingResponse|JsonResponse
    {
        $message = MessageTranslator::translate($message);
        if ($response === 'api') {
            return response()->json(['success' => $success, 'message' => $message, 'data' => $data], $status);
        }
        return new AdvertisingResponse($success, $message, $data, $status);
    }
}
