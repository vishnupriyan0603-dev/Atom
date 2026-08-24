<?php

use CodeIgniter\Test\CIUnitTestCase;

final class ApiVersionTest extends CIUnitTestCase
{
    public function testBaseApiControllerVersionPayloadAndHeaders(): void
    {
        $controller = new class extends \App\Controllers\Api\BaseApiController {
            public function testSuccessResponse() {
                return $this->respondSuccess(['foo' => 'bar'], 'Test message');
            }
            public function testErrorResponse() {
                return $this->respondError('Test error', 400, ['field' => 'invalid']);
            }
        };

        // Test Success Response
        $responseSuccess = $controller->testSuccessResponse();
        $this->assertEquals(200, $responseSuccess->getStatusCode());

        $bodySuccess = json_decode($responseSuccess->getBody(), true);
        $this->assertTrue($bodySuccess['success']);
        $this->assertArrayHasKey('meta', $bodySuccess);
        $this->assertEquals('v1.0', $bodySuccess['meta']['api_version']);
        $this->assertEquals('bar', $bodySuccess['data']['foo']);

        // Test Error Response
        $responseError = $controller->testErrorResponse();
        $this->assertEquals(400, $responseError->getStatusCode());

        $bodyError = json_decode($responseError->getBody(), true);
        $this->assertFalse($bodyError['success']);
        $this->assertArrayHasKey('meta', $bodyError);
        $this->assertEquals('v1.0', $bodyError['meta']['api_version']);
    }
}
