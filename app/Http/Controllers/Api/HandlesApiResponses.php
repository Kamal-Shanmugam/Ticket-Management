<?php

namespace App\Http\Controllers\Api;

trait HandlesApiResponses
{
    /**
     * Return a standardized JSON success response.
     */
    protected function successResponse($data = null, string $message = 'Operation successful', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return a standardized JSON error response.
     */
    protected function errorResponse(string $message, $errors = [], int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => empty($errors) ? new \stdClass() : $errors,
        ], $code);
    }
}
