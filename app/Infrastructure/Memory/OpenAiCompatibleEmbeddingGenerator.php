<?php

namespace App\Infrastructure\Memory;

use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Data\EmbeddingData;
use App\Application\Memory\Exceptions\EmbeddingProviderException;
use App\Support\Security\SecretRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

final class OpenAiCompatibleEmbeddingGenerator implements EmbeddingGenerator
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
        private readonly SecretRedactor $redactor,
    ) {}

    public function generate(string $input): EmbeddingData
    {
        $payload = [
            'model' => $this->config['model'],
            'input' => $input,
            'encoding_format' => 'float',
        ];
        $headers = $this->buildHeaders();

        $this->logRequest($input, $payload, $headers);

        try {
            $response = $this->pendingRequest($headers)
                ->post('/embeddings', $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            $this->logFailure('transport_error', null, ['message' => $exception->getMessage()]);

            throw EmbeddingProviderException::transport('openai_compatible', 'Embedding provider transport failure.');
        } catch (RequestException $exception) {
            $response = $exception->response;
            $normalized = $this->normalizeError($response);

            $this->logFailure(
                errorCode: $normalized->errorCode ?? 'provider_error',
                statusCode: $normalized->statusCode,
                context: $normalized->context,
            );

            throw $normalized;
        } catch (Throwable $exception) {
            $this->logFailure('unexpected_error', null, ['message' => $exception->getMessage()]);

            throw EmbeddingProviderException::response(
                provider: 'openai_compatible',
                message: 'Unexpected embedding provider failure.',
                statusCode: null,
                errorCode: 'unexpected_error',
                retriable: false,
            );
        }

        try {
            $embedding = $this->normalizeEmbedding($response);
        } catch (UnexpectedValueException $exception) {
            $this->logFailure('invalid_embedding_response', $response->status(), [
                'message' => $exception->getMessage(),
                'request_id' => $response->header('x-request-id'),
            ]);

            throw EmbeddingProviderException::response(
                provider: 'openai_compatible',
                message: 'Embedding provider returned an invalid response.',
                statusCode: $response->status(),
                errorCode: 'invalid_embedding_response',
                retriable: false,
                context: [
                    'request_id' => $response->header('x-request-id'),
                ],
            );
        }

        $this->logResponse($response, $embedding);

        return $embedding;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function pendingRequest(array $headers): PendingRequest
    {
        return Http::baseUrl((string) $this->config['base_url'])
            ->acceptJson()
            ->asJson()
            ->timeout((int) $this->config['timeout'])
            ->withHeaders($headers)
            ->retry(
                times: ((int) $this->config['retry_times']) + 1,
                sleepMilliseconds: (int) $this->config['retry_sleep_ms'],
                when: function (Throwable $exception, PendingRequest $request): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException) {
                        return in_array($exception->response?->status(), [408, 409, 429, 500, 502, 503, 504], true);
                    }

                    return false;
                },
                throw: true,
            );
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->config['api_key'],
        ];

        if ($this->config['organization']) {
            $headers['OpenAI-Organization'] = (string) $this->config['organization'];
        }

        if ($this->config['project']) {
            $headers['OpenAI-Project'] = (string) $this->config['project'];
        }

        return $headers;
    }

    private function normalizeEmbedding(Response $response): EmbeddingData
    {
        $body = $response->json();
        $vector = data_get($body, 'data.0.embedding');

        if (! is_array($vector) || $vector === []) {
            throw new UnexpectedValueException('Embedding vector is missing.');
        }

        $normalized = array_map(static function (mixed $value): float {
            if (! is_int($value) && ! is_float($value)) {
                throw new UnexpectedValueException('Embedding vector contains a non-numeric value.');
            }

            return (float) $value;
        }, array_values($vector));

        return new EmbeddingData(
            vector: $normalized,
            model: (string) data_get($body, 'model', $this->config['model']),
        );
    }

    private function normalizeError(Response $response): EmbeddingProviderException
    {
        $body = $response->json();
        $statusCode = $response->status();
        $errorCode = data_get($body, 'error.code') ?? data_get($body, 'error.type') ?? 'provider_error';
        $message = data_get($body, 'error.message', 'Embedding provider request failed.');
        $retriable = in_array($statusCode, [408, 409, 429, 500, 502, 503, 504], true);

        return EmbeddingProviderException::response(
            provider: 'openai_compatible',
            message: $message,
            statusCode: $statusCode,
            errorCode: (string) $errorCode,
            retriable: $retriable,
            context: [
                'request_id' => $response->header('x-request-id'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    private function logRequest(string $input, array $payload, array $headers): void
    {
        Log::info('embedding.provider.request', [
            'provider' => 'openai_compatible',
            'endpoint' => '/embeddings',
            'base_url' => $this->redactor->redactString((string) $this->config['base_url']),
            'headers' => $this->redactor->redactArray($headers),
            'payload' => [
                'model' => $payload['model'],
                'encoding_format' => $payload['encoding_format'],
                'input_length' => mb_strlen($input),
                'input_sha256' => hash('sha256', $input),
            ],
        ]);
    }

    private function logResponse(Response $response, EmbeddingData $embedding): void
    {
        Log::info('embedding.provider.response', [
            'provider' => 'openai_compatible',
            'status_code' => $response->status(),
            'request_id' => $response->header('x-request-id'),
            'model' => $embedding->model,
            'dimensions' => $embedding->dimensions(),
            'input_tokens' => data_get($response->json(), 'usage.prompt_tokens'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailure(string $errorCode, ?int $statusCode, array $context): void
    {
        Log::warning('embedding.provider.failure', [
            'provider' => 'openai_compatible',
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'context' => $this->redactor->redactArray($context),
        ]);
    }
}
