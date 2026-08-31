<?php

namespace App\Services\Ai;

use App\Exceptions\AiServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiServiceClient
{
    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        return $this->getJson('/api/v1/health');
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array
    {
        return $this->getJson('/api/v1/capabilities');
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $uri): array
    {
        $correlationId = (string) Str::uuid();

        try {
            $response = $this->request($correlationId)->get($uri);
        } catch (ConnectionException $exception) {
            throw new AiServiceException(
                message: 'Unable to connect to the AI service.',
                correlationId: $correlationId,
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw $this->remoteFailure(
                response: $response,
                fallbackCorrelationId: $correlationId,
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new AiServiceException(
                message: 'AI service returned an invalid JSON response.',
                statusCode: $response->status(),
                correlationId: $this->correlationId(
                    response: $response,
                    fallback: $correlationId,
                ),
            );
        }

        return $payload;
    }

    private function request(string $correlationId): PendingRequest
    {
        return Http::baseUrl(
            rtrim(
                $this->requiredConfigString('services.ai_service.base_url'),
                '/',
            ),
        )
            ->acceptJson()
            ->withHeaders([
                'X-Internal-API-Key' => $this->requiredConfigString(
                    'services.ai_service.internal_api_key',
                ),
                'X-Correlation-ID' => $correlationId,
            ])
            ->connectTimeout(
                (int) config('services.ai_service.connect_timeout', 10),
            )
            ->timeout(
                (int) config('services.ai_service.timeout', 600),
            );
    }

    private function remoteFailure(
        Response $response,
        string $fallbackCorrelationId,
    ): AiServiceException {
        $payload = $response->json();

        $message = is_array($payload)
            ? data_get($payload, 'error.message')
            ?? data_get($payload, 'detail')
            ?? 'AI service request failed.'
            : 'AI service request failed.';

        return new AiServiceException(
            message: (string) $message,
            statusCode: $response->status(),
            correlationId: $this->correlationId(
                response: $response,
                fallback: $fallbackCorrelationId,
            ),
        );
    }

    private function correlationId(
        Response $response,
        string $fallback,
    ): string {
        $header = $response->header('X-Correlation-ID');

        if (is_string($header) && $header !== '') {
            return $header;
        }

        $payload = $response->json();
        $correlationId = is_array($payload)
            ? data_get($payload, 'correlation_id')
            : null;

        return is_string($correlationId) && $correlationId !== ''
            ? $correlationId
            : $fallback;
    }

    private function requiredConfigString(string $key): string
    {
        $value = config($key);

        if (! is_string($value) || trim($value) === '') {
            throw new AiServiceException(
                message: "Missing required AI service configuration: {$key}.",
            );
        }

        return $value;
    }
}
