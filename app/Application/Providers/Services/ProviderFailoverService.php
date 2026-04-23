<?php

namespace App\Application\Providers\Services;

use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Support\Exceptions\InvalidStateException;
use App\Support\Observability\ObservabilityService;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProviderFailoverService implements LlmProvider
{
    /**
     * @param  array<string, LlmProvider>  $providers
     * @param  array<int, string>  $order
     */
    public function __construct(
        private readonly array $providers,
        private readonly array $order,
        private readonly bool $fallbackOnRetriableOnly = true,
        private readonly ?ObservabilityService $observability = null,
    ) {}

    public function generate(LlmRequestData $request): LlmResponseData
    {
        if ($this->order === []) {
            throw new InvalidStateException('Provider failover requires at least one configured provider.');
        }

        $attempts = [];
        $lastException = null;

        foreach ($this->order as $index => $name) {
            $provider = $this->providers[$name] ?? null;

            if (! $provider instanceof LlmProvider) {
                throw new InvalidStateException("Provider failover route [{$name}] is not registered.");
            }

            try {
                $response = $provider->generate($request);

                if ($index > 0) {
                    Log::info('llm.provider.failover_succeeded', [
                        'selected_provider' => $name,
                        'attempts' => $attempts,
                    ]);
                    $this->observability?->metric('llm.provider.failover_total', 1, [
                        'selected_provider' => $name,
                        'attempt_count' => count($attempts) + 1,
                        'outcome' => 'recovered',
                    ]);
                }

                return $response;
            } catch (LlmProviderException $exception) {
                $lastException = $exception;
                $attempts[] = $this->attemptContext($name, $exception);

                if (! $this->shouldFallback($exception, $index)) {
                    Log::warning('llm.provider.failover_stopped', [
                        'provider' => $name,
                        'attempts' => $attempts,
                    ]);
                    $this->observability?->metric('llm.provider.failover_total', 1, [
                        'selected_provider' => $name,
                        'attempt_count' => count($attempts),
                        'outcome' => 'stopped',
                    ]);

                    throw $exception;
                }
            } catch (Throwable $exception) {
                $normalized = LlmProviderException::response(
                    provider: $name,
                    message: 'Unexpected provider failure.',
                    statusCode: null,
                    errorCode: 'unexpected_error',
                    retriable: false,
                    context: [
                        'exception' => $exception::class,
                    ],
                );

                $lastException = $normalized;
                $attempts[] = $this->attemptContext($name, $normalized);

                Log::warning('llm.provider.failover_stopped', [
                    'provider' => $name,
                    'attempts' => $attempts,
                ]);
                $this->observability?->metric('llm.provider.failover_total', 1, [
                    'selected_provider' => $name,
                    'attempt_count' => count($attempts),
                    'outcome' => 'unexpected_error',
                ]);

                throw $normalized;
            }
        }

        Log::warning('llm.provider.failover_exhausted', [
            'attempts' => $attempts,
        ]);
        $this->observability?->metric('llm.provider.failover_total', 1, [
            'selected_provider' => null,
            'attempt_count' => count($attempts),
            'outcome' => 'exhausted',
        ]);

        throw $lastException ?? new InvalidStateException('Provider failover exhausted without a provider failure.');
    }

    private function shouldFallback(LlmProviderException $exception, int $index): bool
    {
        if ($index >= count($this->order) - 1) {
            return false;
        }

        return ! $this->fallbackOnRetriableOnly || $exception->retriable;
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptContext(string $name, LlmProviderException $exception): array
    {
        return [
            'provider' => $name,
            'status_code' => $exception->statusCode,
            'error_code' => $exception->errorCode,
            'retriable' => $exception->retriable,
        ];
    }
}
