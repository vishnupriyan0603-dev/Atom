<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

abstract class BaseApiController extends ResourceController
{
    protected $format = 'json';

    protected function respondSuccess($data = null, string $message = 'Success', int $code = 200)
    {
        return $this->respond([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function respondError(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = ['success' => false, 'message' => $message];
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
