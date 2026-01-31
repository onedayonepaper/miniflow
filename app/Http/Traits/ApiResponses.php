<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponses
{
    /**
     * Success response with data
     */
    protected function success(
        mixed $data = null,
        string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
                return $data->additional($response)->response()->setStatusCode($statusCode);
            }
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Created response (201)
     */
    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * No content response (204)
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Error response
     */
    protected function error(
        string $message,
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Not found response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Forbidden response
     */
    protected function forbidden(string $message = 'Access denied'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Validation error response
     */
    protected function validationError(array $errors): JsonResponse
    {
        return $this->error('Validation failed', 422, $errors);
    }

    /**
     * Paginated response
     */
    protected function paginated(ResourceCollection $resource): JsonResponse
    {
        return $resource->additional(['success' => true])->response();
    }
}
