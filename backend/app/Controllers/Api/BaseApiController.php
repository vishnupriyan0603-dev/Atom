<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

abstract class BaseApiController extends ResourceController
{
    protected $format = 'json';

    /** @var string|null Per-request correlation ID. */
    protected ?string $requestId = null;

    /**
     * Returns the authenticated user's ID, or null when unauthenticated.
     */
    protected function currentUserId(): ?int
    {
        $user = $this->request->user ?? null;
        return $user ? (int) $user->id : null;
    }

    /**
     * Returns the request ID for this HTTP request, generating it lazily.
     * Format: ATOM-YYYYMMDD-XXXXXXXX (matching the trace system).
     */
    protected function requestId(): string
    {
        if ($this->requestId === null) {
            $this->requestId = 'ATOM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $this->requestId;
    }

    protected function respondSuccess($data = null, string $message = 'Success', int $code = 200)
    {
        return $this->respond([
            'success'    => true,
            'message'    => $message,
            'data'       => $data,
            'request_id' => $this->requestId(),
        ], $code);
    }

    protected function respondError(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            'success'    => false,
            'message'    => $message,
            'request_id' => $this->requestId(),
        ];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        return $this->respond($response, $code);
    }

    protected function respondCreated($data = null, string $message = 'Created successfully')
    {
        return $this->respondSuccess($data, $message, 201);
    }

    protected function respondNoContent(string $message = 'No Content')
    {
        return $this->respond(null, 204);
    }
}
