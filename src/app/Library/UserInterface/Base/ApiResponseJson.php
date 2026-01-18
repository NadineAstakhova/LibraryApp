<?php

namespace App\Library\UserInterface\Base;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponseJson
{
    public static function successJsonResponse(array $data = [], int $code = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
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