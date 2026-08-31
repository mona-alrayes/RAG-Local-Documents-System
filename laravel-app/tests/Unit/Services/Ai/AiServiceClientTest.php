<?php

namespace Tests\Unit\Services\Ai;

use App\Exceptions\AiServiceException;
use App\Services\Ai\AiServiceClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiServiceClientTest extends TestCase
{
    public function test_health_returns_payload_and_sends_required_internal_headers(): void
    {
        config([
            'services.ai_service.base_url' => 'http://ai-service.test',
            'services.ai_service.internal_api_key' => 'test-secret',
            'services.ai_service.connect_timeout' => 10,
            'services.ai_service.timeout' => 600,
        ]);

        Http::fake([
            'http://ai-service.test/api/v1/health' => Http::response([
                'status' => 'ok',
            ]),
        ]);

        $result = app(AiServiceClient::class)->health();

        $this->assertSame([
            'status' => 'ok',
        ], $result);

        Http::assertSent(function (Request $request): bool {
            $correlationId = $request->header('X-Correlation-ID')[0] ?? null;

            return $request->url() === 'http://ai-service.test/api/v1/health'
                && $request->hasHeader('Accept', 'application/json')
                && $request->hasHeader('X-Internal-API-Key', 'test-secret')
                && is_string($correlationId)
                && Str::isUuid($correlationId);
        });
    }

    public function test_health_wraps_remote_http_failure_in_ai_service_exception(): void
    {
        config([
            'services.ai_service.base_url' => 'http://ai-service.test',
            'services.ai_service.internal_api_key' => 'test-secret',
        ]);

        Http::fake([
            'http://ai-service.test/api/v1/health' => Http::response(
                ['detail' => 'Unauthorized'],
                401,
                ['X-Correlation-ID' => 'remote-correlation-id'],
            ),
        ]);

        try {
            app(AiServiceClient::class)->health();

            $this->fail('Expected AiServiceException was not thrown.');
        } catch (AiServiceException $exception) {
            $this->assertSame('Unauthorized', $exception->getMessage());
            $this->assertSame(401, $exception->statusCode);
            $this->assertSame(
                'remote-correlation-id',
                $exception->correlationId,
            );
        }
    }

    public function test_health_wraps_connection_failure_in_ai_service_exception(): void
    {
        config([
            'services.ai_service.base_url' => 'http://ai-service.test',
            'services.ai_service.internal_api_key' => 'test-secret',
        ]);

        Http::fake([
            'http://ai-service.test/api/v1/health' => Http::failedConnection(),
        ]);

        try {
            app(AiServiceClient::class)->health();

            $this->fail('Expected AiServiceException was not thrown.');
        } catch (AiServiceException $exception) {
            $this->assertSame(
                'Unable to connect to the AI service.',
                $exception->getMessage(),
            );

            $this->assertNull($exception->statusCode);
            $this->assertTrue(Str::isUuid($exception->correlationId));
            $this->assertInstanceOf(
                ConnectionException::class,
                $exception->getPrevious(),
            );
        }
    }

    public function test_health_rejects_invalid_json_response(): void
    {
        config([
            'services.ai_service.base_url' => 'http://ai-service.test',
            'services.ai_service.internal_api_key' => 'test-secret',
        ]);

        Http::fake([
            'http://ai-service.test/api/v1/health' => Http::response(
                'not-json',
                200,
                [
                    'Content-Type' => 'text/plain',
                    'X-Correlation-ID' => 'invalid-json-correlation-id',
                ],
            ),
        ]);

        try {
            app(AiServiceClient::class)->health();

            $this->fail('Expected AiServiceException was not thrown.');
        } catch (AiServiceException $exception) {
            $this->assertSame(
                'AI service returned an invalid JSON response.',
                $exception->getMessage(),
            );

            $this->assertSame(200, $exception->statusCode);
            $this->assertSame(
                'invalid-json-correlation-id',
                $exception->correlationId,
            );
        }
    }

    public function test_capabilities_returns_payload(): void
    {
        config([
            'services.ai_service.base_url' => 'http://ai-service.test',
            'services.ai_service.internal_api_key' => 'test-secret',
        ]);

        Http::fake([
            'http://ai-service.test/api/v1/capabilities' => Http::response([
                'deployment_mode' => 'local',
                'profiles' => [
                    'cloud',
                    'hybrid_local',
                ],
            ]),
        ]);

        $result = app(AiServiceClient::class)->capabilities();

        $this->assertSame([
            'deployment_mode' => 'local',
            'profiles' => [
                'cloud',
                'hybrid_local',
            ],
        ], $result);

        Http::assertSent(
            fn (Request $request): bool => $request->url()
                === 'http://ai-service.test/api/v1/capabilities',
        );
    }

    public function test_health_fails_before_request_when_internal_api_key_is_missing(): void
    {
        config([
            'services.ai_service.base_url' => 'http://ai-service.test',
            'services.ai_service.internal_api_key' => null,
        ]);

        Http::fake();

        try {
            app(AiServiceClient::class)->health();

            $this->fail('Expected AiServiceException was not thrown.');
        } catch (AiServiceException $exception) {
            $this->assertSame(
                'Missing required AI service configuration: services.ai_service.internal_api_key.',
                $exception->getMessage(),
            );

            $this->assertNull($exception->statusCode);
            $this->assertNull($exception->correlationId);
        }

        Http::assertNothingSent();
    }

    public function test_health_reads_structured_fastapi_error_payload(): void
    {
        config([
            'services.ai_service.base_url' => 'http://ai-service.test',
            'services.ai_service.internal_api_key' => 'test-secret',
        ]);

        Http::fake([
            'http://ai-service.test/api/v1/health' => Http::response(
                [
                    'error' => [
                        'code' => 'internal_error',
                        'message' => 'Processing service failed.',
                    ],
                    'correlation_id' => 'body-correlation-id',
                ],
                500,
            ),
        ]);

        try {
            app(AiServiceClient::class)->health();

            $this->fail('Expected AiServiceException was not thrown.');
        } catch (AiServiceException $exception) {
            $this->assertSame(
                'Processing service failed.',
                $exception->getMessage(),
            );

            $this->assertSame(500, $exception->statusCode);
            $this->assertSame(
                'body-correlation-id',
                $exception->correlationId,
            );
        }
    }
}
