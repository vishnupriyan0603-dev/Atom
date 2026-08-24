<?php

namespace App\Controllers\Api;

use Atom\Telemetry\TelemetryManager;

class Telemetry extends BaseApiController
{
    public function metrics()
    {
        $manager = TelemetryManager::getInstance();
        return $this->respondSuccess($manager->getMetrics());
    }

    public function spans()
    {
        $manager = TelemetryManager::getInstance();
        return $this->respondSuccess($manager->getCompletedSpans());
    }
}
