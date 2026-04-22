<?php

namespace App\Infrastructure\Providers;

use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Application\Providers\Exceptions\LlmProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OpenAiCompatibleProvider implements LlmProvider
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function generate(LlmRequestData $request): LlmResponseData
    {
        $payload = array_filter(
            $request->toArray($this->config['model']),
            static fn (mixed $value): bool => $value !== null,
        );
        $headers = $this->buildHeaders($request);

        $this->logRequest($payload, $headers);

        try {
            $response = $this->pendingRequest($headers)
                ->post('/chat/completions', $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            $this->logFailure('transport_error', null, ['message' => $exception->getMessage()]);

            throw LlmProviderException::transport('openai_compatible', 'Provider transport failure.');
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

            throw LlmProviderException::response(
                provider: 'openai_compatible',
                message: 'Unexpected provider failure.',
                statusCode: null,
                errorCode: 'unexpected_error',
                retriable: false,
            );
        }

        $body = $response->json();
        $normalized = new LlmResponseData(
            provider: 'openai_compatible',
            responseId: (string) data_get($body, 'id', ''),
            model: (string) data_get($body, 'model', $payload['model']),
            content: $this->extractContent($body),
            finishReason: data_get($body, 'choices.0.finish_reason'),
            inputTokens: data_get($body, 'usage.prompt_tokens'),
            outputTokens: data_get($body, 'usage.completion_tokens'),
            requestId: $response->header('x-request-id'),
        );

        $this->logResponse($response, $normalized);

        return $normalized;
    }

    private function pendingRequest(array $headers): PendingRequest
    {
        return Http::baseUrl($this->config['base_url'])
            ->acceptJson()
            ->asJson()
            ->timeout($this->config['timeout'])
            ->withHeaders($headers)
            ->retry(
                times: $this->config['retry_times'] + 1,
                sleepMilliseconds: $this->config['retry_sleep_ms'],
                when: function (Throwable $exception, PendingRequest $request): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof RequestException) {
                        $status = $exception->response?->status();

                        return in_array($status, [408, 409, 429, 500, 502, 503, 504], true);
                    }

                    return false;
                },
                throw: true,
            );
    }

    private function buildHeaders(LlmRequestData $request): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->config['api_key'],
        ];

        if ($this->config['organization']) {
            $headers['OpenAI-Organization'] = $this->config['organization'];
        }

        if ($this->config['project']) {
            $headers['OpenAI-Project'] = $this->config['project'];
        }

        if ($request->idempotencyKey !== null) {
            $headers['X-Client-Request-Id'] = $request->idempotencyKey;
        }

        return $headers;
    }

    private function extractContent(array $body): string
    {
        $content = data_get($body, 'choices.0.message.content');

        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            return collect($content)
                ->map(static function (mixed $part): string {
                    if (is_array($part) && ($part['type'] ?? null) === 'text') {
                        return (string) ($part['text'] ?? '');
                    }

                    return '';
                })
                ->filter()
                ->implode("\n");
        }

        return '';
    }

    private function normalizeError(Response $response): LlmProviderException
    {
        $body = $response->json();
        $statusCode = $response->status();
        $errorCode = data_get($body, 'error.code') ?? data_get($body, 'error.type') ?? 'provider_error';
        $message = data_get($body, 'error.message', 'Provider request failed.');
        $retriable = in_array($statusCode, [408, 409, 429, 500, 502, 503, 504], true);

        return LlmProviderException::response(
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

    private function logRequest(array $payload, array $headers): void
    {
        Log::info('llm.provider.request', [
            'provider' => 'openai_compatible',
            'endpoint' => '/chat/completions',
            'base_url' => $this->config['base_url'],
            'headers' => $this->redactHeaders($headers),
            'payload' => [
                'model' => $payload['model'],
                'temperature' => $payload['temperature'] ?? null,
                'store' => $this->config['store'],
                'message_count' => count($payload['messages']),
                'message_roles' => array_values(array_map(
                    static fn (array $message): string => (string) $message['role'],
                    $payload['messages'],
                )),
                'metadata_keys' => array_keys($payload['metadata'] ?? []),
            ],
        ]);
    }

    private function logResponse(Response $response, LlmResponseData $normalized): void
    {
        Log::info('llm.provider.response', [
            'provider' => 'openai_compatible',
            'status_code' => $response->status(),
            'request_id' => $normalized->requestId,
            'response_id' => $normalized->responseId,
            'model' => $normalized->model,
            'finish_reason' => $normalized->finishReason,
            'input_tokens' => $normalized->inputTokens,
            'output_tokens' => $normalized->outputTokens,
            'content_length' => mb_strlen($normalized->content),
        ]);
    }

    private function logFailure(string $errorCode, ?int $statusCode, array $context): void
    {
        Log::warning('llm.provider.failure', [
            'provider' => 'openai_compatible',
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'context' => $context,
        ]);
    }

    private function redactHeaders(array $headers): array
    {
        return collect($headers)
            ->mapWithKeys(static function (string $value, string $key): array {
                if (in_array(strtolower($key), ['authorization', 'openai-organization', 'openai-project'], true)) {
                    return [$key => '[REDACTED]'];
                }

                return [$key => $value];
            })
            ->all();
    }
}
