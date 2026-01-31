<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * RFC 7807 Problem Details for HTTP APIs
 */
class ApiException extends Exception
{
    protected string $type;
    protected string $title;
    protected ?string $detail;
    protected int $statusCode;
    protected array $extensions;

    public function __construct(
        string $title,
        int $statusCode = 400,
        ?string $detail = null,
        string $type = 'about:blank',
        array $extensions = []
    ) {
        parent::__construct($detail ?? $title, $statusCode);

        $this->type = $type;
        $this->title = $title;
        $this->detail = $detail;
        $this->statusCode = $statusCode;
        $this->extensions = $extensions;
    }

    public function render(): JsonResponse
    {
        $response = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->statusCode,
        ];

        if ($this->detail) {
            $response['detail'] = $this->detail;
        }

        if (!empty($this->extensions)) {
            $response = array_merge($response, $this->extensions);
        }

        return response()->json($response, $this->statusCode, [
            'Content-Type' => 'application/problem+json',
        ]);
    }

    public static function notFound(string $resource = 'Resource'): self
    {
        return new self(
            title: "{$resource} not found",
            statusCode: 404,
            type: 'https://httpstatuses.com/404'
        );
    }

    public static function unauthorized(string $detail = 'Authentication required'): self
    {
        return new self(
            title: 'Unauthorized',
            statusCode: 401,
            detail: $detail,
            type: 'https://httpstatuses.com/401'
        );
    }

    public static function forbidden(string $detail = 'Access denied'): self
    {
        return new self(
            title: 'Forbidden',
            statusCode: 403,
            detail: $detail,
            type: 'https://httpstatuses.com/403'
        );
    }

    public static function validationFailed(array $errors): self
    {
        return new self(
            title: 'Validation Failed',
            statusCode: 422,
            detail: 'The given data was invalid.',
            type: 'https://httpstatuses.com/422',
            extensions: ['errors' => $errors]
        );
    }

    public static function conflict(string $detail): self
    {
        return new self(
            title: 'Conflict',
            statusCode: 409,
            detail: $detail,
            type: 'https://httpstatuses.com/409'
        );
    }

    public static function businessError(string $detail, array $extensions = []): self
    {
        return new self(
            title: 'Business Rule Violation',
            statusCode: 422,
            detail: $detail,
            type: 'https://httpstatuses.com/422',
            extensions: $extensions
        );
    }

    public static function serverError(string $detail = 'An unexpected error occurred'): self
    {
        return new self(
            title: 'Internal Server Error',
            statusCode: 500,
            detail: $detail,
            type: 'https://httpstatuses.com/500'
        );
    }
}
