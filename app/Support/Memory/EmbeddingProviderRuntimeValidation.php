<?php

namespace App\Support\Memory;

use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Exceptions\EmbeddingProviderException;
use App\Support\Security\SecretRedactor;
use Throwable;

final readonly class EmbeddingProviderRuntimeValidation
{
    public function __construct(
        private EmbeddingGenerator $generator,
        private SecretRedactor $redactor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $provider = (string) config('providers.embeddings.default', 'null');
        $providerConfig = $this->providerConfig($provider);
        $memoryDimensions = (int) config('memory.pgvector.dimensions', 1536);
        $probeInput = 'Production embedding runtime validation probe.';

        $runtime = [
            'probe_input_length' => mb_strlen($probeInput),
            'probe_input_sha256' => hash('sha256', $probeInput),
            'normalized_model' => null,
            'normalized_dimensions' => null,
            'transport' => [
                'base_url' => $this->redactor->redactString((string) ($providerConfig['base_url'] ?? '')),
                'timeout' => isset($providerConfig['timeout']) ? (int) $providerConfig['timeout'] : null,
            ],
            'probe_error' => null,
        ];

        $checks = [
            'provider_supported' => in_array($provider, ['openai_compatible', 'null'], true),
            'real_provider_selected' => $provider !== 'null',
            'model_configured' => filled($providerConfig['model'] ?? null),
            'base_url_configured' => $provider === 'null' || filled($providerConfig['base_url'] ?? null),
            'base_url_uses_https' => $provider === 'null' || $this->usesHttps((string) ($providerConfig['base_url'] ?? '')),
            'api_key_present' => $provider === 'null' || filled($providerConfig['api_key'] ?? null),
            'probe_completed' => false,
            'normalized_model_detected' => false,
            'normalized_vector_present' => false,
            'normalized_dimensions_positive' => false,
            'dimensions_match_memory_config' => false,
        ];

        if (! $checks['provider_supported']) {
            return $this->buildReport($provider, $providerConfig, $memoryDimensions, $runtime, $checks);
        }

        if (! $checks['real_provider_selected']) {
            return $this->buildReport($provider, $providerConfig, $memoryDimensions, $runtime, $checks);
        }

        if (
            ! $checks['model_configured']
            || ! $checks['base_url_configured']
            || ! $checks['base_url_uses_https']
            || ! $checks['api_key_present']
        ) {
            return $this->buildReport($provider, $providerConfig, $memoryDimensions, $runtime, $checks);
        }

        try {
            $embedding = $this->generator->generate($probeInput);

            $runtime['normalized_model'] = $embedding->model;
            $runtime['normalized_dimensions'] = $embedding->dimensions();

            $checks['probe_completed'] = true;
            $checks['normalized_model_detected'] = $embedding->model !== '';
            $checks['normalized_vector_present'] = $embedding->vector !== [];
            $checks['normalized_dimensions_positive'] = $embedding->dimensions() > 0;
            $checks['dimensions_match_memory_config'] = $embedding->dimensions() === $memoryDimensions;
        } catch (EmbeddingProviderException $exception) {
            $runtime['probe_error'] = [
                'message' => $this->redactor->redactString($exception->getMessage()),
                'provider' => $exception->provider,
                'status_code' => $exception->statusCode,
                'error_code' => $exception->errorCode,
                'retriable' => $exception->retriable,
            ];
        } catch (Throwable $exception) {
            $runtime['probe_error'] = [
                'message' => $this->redactor->redactString($exception->getMessage()),
                'provider' => $provider,
                'status_code' => null,
                'error_code' => 'unexpected_error',
                'retriable' => false,
            ];
        }

        return $this->buildReport($provider, $providerConfig, $memoryDimensions, $runtime, $checks);
    }

    /**
     * @param  array<string, mixed>  $providerConfig
     * @param  array<string, mixed>  $runtime
     * @param  array<string, bool>  $checks
     * @return array<string, mixed>
     */
    private function buildReport(
        string $provider,
        array $providerConfig,
        int $memoryDimensions,
        array $runtime,
        array $checks,
    ): array {
        return [
            'environment' => (string) config('app.env'),
            'provider' => $provider,
            'config' => [
                'model' => $providerConfig['model'] ?? null,
                'memory_dimensions' => $memoryDimensions,
                'fallback_provider' => 'null',
            ],
            'runtime' => $runtime,
            'checks' => $checks,
            'fallback' => [
                'safe' => true,
                'null_provider_available' => true,
                'requires_config_switch' => $provider !== 'null',
                'degraded_retrieval_returns_empty_context' => true,
            ],
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function providerConfig(string $provider): array
    {
        return config("providers.embeddings.{$provider}", []);
    }

    private function usesHttps(string $baseUrl): bool
    {
        if ($baseUrl === '') {
            return false;
        }

        return parse_url($baseUrl, PHP_URL_SCHEME) === 'https';
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
