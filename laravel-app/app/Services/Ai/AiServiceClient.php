<?php

namespace App\Services\Ai;

use App\Enums\ProcessingProfile;
use App\Exceptions\AiServiceException;
use App\Services\Ai\Data\ProcessDocumentRequestData;
use App\Services\Ai\Data\ProcessDocumentResult;
use App\Services\Ai\Validation\ProcessDocumentResponseValidator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use UnexpectedValueException;

class AiServiceClient
{
    public function __construct(
        private readonly ProcessDocumentResponseValidator $processDocumentResponseValidator,
    ) {}

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

    public function processDocument(
        ProcessDocumentRequestData $data,
        string $filePath,
        string $fileName,
    ): ProcessDocumentResult {
        $correlationId = (string) Str::uuid();
        $stream = $this->openDocumentStream($filePath);

        try {
            try {
                $response = $this->request($correlationId)
                    ->timeout(
                        (int) config(
                            'services.ai_service.process_document_timeout',
                            300,
                        ),
                    )
                    ->attach(
                        'file',
                        $stream,
                        $this->safeFileName($fileName, $data),
                    )
                    ->post(
                        '/api/v1/documents/process',
                        $data->toArray(),
                    );
            } catch (ConnectionException $exception) {
                throw new AiServiceException(
                    message: 'Unable to connect to the AI service.',
                    correlationId: $correlationId,
                    previous: $exception,
                );
            }
        } finally {
            fclose($stream);
        }

        if ($response->failed()) {
            throw $this->remoteFailure(
                response: $response,
                fallbackCorrelationId: $correlationId,
            );
        }

        return $this->processDocumentResult(
            response: $response,
            requestData: $data,
            fallbackCorrelationId: $correlationId,
        );
    }

    public function deleteProcessingRunPoints(
        int $userId,
        int $documentId,
        int $processingRunId,
        ProcessingProfile $processingProfile,
    ): void {
        $correlationId = (string) Str::uuid();

        try {
            $response = $this->request($correlationId)
                ->delete(
                    '/api/v1/documents/processing-runs/points',
                    [
                        'user_id' => $userId,
                        'document_id' => $documentId,
                        'processing_run_id' => $processingRunId,
                        'processing_profile' => $processingProfile->value,
                    ],
                );
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

        if (
            ! is_array($payload)
            || data_get($payload, 'status') !== 'deleted'
            || (int) data_get($payload, 'document_id') !== $documentId
            || (int) data_get($payload, 'processing_run_id')
            !== $processingRunId
        ) {
            throw new AiServiceException(
                message: 'AI service returned an invalid processing run cleanup response.',
                statusCode: $response->status(),
                correlationId: $this->correlationId(
                    response: $response,
                    fallback: $correlationId,
                ),
            );
        }
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

    /**
     * @return resource
     */
    private function openDocumentStream(string $filePath)
    {
        try {
            $disk = Storage::disk('documents');

            if (! $disk->exists($filePath)) {
                throw new AiServiceException(
                    message: 'Document file is missing from private storage.',
                );
            }

            $stream = $disk->readStream($filePath);
        } catch (AiServiceException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new AiServiceException(
                message: 'Unable to read document from private storage.',
                previous: $exception,
            );
        }

        if (! is_resource($stream) || ! $this->isReadableStream($stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }

            throw new AiServiceException(
                message: 'Unable to read document from private storage.',
            );
        }

        return $stream;
    }

    /**
     * @param  resource  $stream
     */
    private function isReadableStream($stream): bool
    {
        $mode = stream_get_meta_data($stream)['mode'] ?? null;

        return is_string($mode)
            && (str_contains($mode, 'r') || str_contains($mode, '+'));
    }

    private function safeFileName(
        string $fileName,
        ProcessDocumentRequestData $data,
    ): string {
        $normalized = str_replace('\\', '/', $fileName);
        $basename = trim(basename($normalized));

        return $basename !== ''
            ? $basename
            : 'document.'.$data->fileType->value;
    }

    private function processDocumentResult(
        Response $response,
        ProcessDocumentRequestData $requestData,
        string $fallbackCorrelationId,
    ): ProcessDocumentResult {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw $this->invalidProcessDocumentResponse(
                response: $response,
                fallbackCorrelationId: $fallbackCorrelationId,
            );
        }

        try {
            $validated = $this->processDocumentResponseValidator->validate(
                payload: $payload,
                requestData: $requestData,
            );
        } catch (UnexpectedValueException) {
            throw $this->invalidProcessDocumentResponse(
                response: $response,
                fallbackCorrelationId: $fallbackCorrelationId,
            );
        }

        return ProcessDocumentResult::fromValidatedResponse($validated);
    }

    private function invalidProcessDocumentResponse(
        Response $response,
        string $fallbackCorrelationId,
    ): AiServiceException {
        return new AiServiceException(
            message: 'AI service returned an invalid process document response.',
            statusCode: $response->status(),
            correlationId: $this->correlationId(
                response: $response,
                fallback: $fallbackCorrelationId,
            ),
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

        $errorCode = is_array($payload)
            ? data_get($payload, 'error.code')
            : null;

        return new AiServiceException(
            message: (string) $message,
            statusCode: $response->status(),
            correlationId: $this->correlationId(
                response: $response,
                fallback: $fallbackCorrelationId,
            ),
            errorCode: is_string($errorCode) && $errorCode !== ''
                ? $errorCode
                : null,
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
