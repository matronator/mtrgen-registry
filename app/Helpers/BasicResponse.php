<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BasicResponse
{
    public const STATUS_ERROR = 'error';
    public const STATUS_SUCCESS = 'success';

    public function __construct(
        public string $message,
        public string $status = self::STATUS_SUCCESS,
        public int $code = Response::HTTP_OK,
        array $headers = [],
        array $rest = [],
    ) {
        return response()->json(['status' => $status, 'message' => $message, ...$rest], $code, $headers);
    }

    public static function send(
        string $message,
        string $status = self::STATUS_SUCCESS,
        int $code = Response::HTTP_OK,
        array $headers = [],
        array $rest = [],
    ): JsonResponse
    {
        return response()->json(['status' => $status, 'message' => $message, ...$rest], $code, $headers);
    }

    public static function toArray(string $message, string $status = self::STATUS_SUCCESS)
    {
        return ['status' => $status, 'message' => $message];
    }
}
