<?php

namespace App\Library\UserInterface\Base;

use Illuminate\Http\JsonResponse;

class ApiResponseJson
{
    public static function successJsonResponse(string $message, array $data, int $code = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $code);
    }
    public static function errorJsonResponse(string $error, int $code): JsonResponse
    {
        return response()->json([
            'error' => $error,
        ], $code);
    }
}