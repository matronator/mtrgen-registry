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
        public ?ErrorCode $error = null,
        array $headers = [],
        array $rest = [],
    ) {
        $array = ['status' => $status, 'message' => $message, ...$rest];
        if ($error) {
            $array['error'] = $error->value;
        }
        return response()->json($array, $code, $headers);
    }

    public static function send(
        string $message,
        string $status = self::STATUS_SUCCESS,
        int $code = Response::HTTP_OK,
        ?ErrorCode $error = null,
        array $headers = [],
        array $rest = [],
    ): JsonResponse
    {
        $array = ['status' => $status, 'message' => $message, ...$rest];
        if ($error) {
            $array['error'] = $error->value;
        }
        return response()->json($array, $code, $headers);
    }

    public static function toArray(string $message, string $status = self::STATUS_SUCCESS, ?ErrorCode $error = null)
    {
        $array = ['status' => $status, 'message' => $message];
        if ($error) {
            $array['error'] = $error->value;
        }
        return $array;
    }
}
