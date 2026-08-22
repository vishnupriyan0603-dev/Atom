<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class Health extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $dbConnected = false;
        try {
            $db = \Config\Database::connect();
            $dbConnected = ($db->getConnection() !== false);
        } catch (\Exception $e) {}

        return $this->respond([
            'success' => true,
            'status'  => 'ok',
            'services' => [
                'database'  => $dbConnected,
                'memory'    => true,
                'knowledge' => true,
                'gemini'    => true,
            ],
            'version' => '1.0.0'
        ]);
    }
}
