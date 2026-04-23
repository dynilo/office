<?php

namespace App\Support\Observability;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ObservabilityService
{
    /**
     * @param  array<string, mixed>  $dimensions
     */
    public function metric(string $name, int|float $value = 1, array $dimensions = []): void
    {
        if (! $this->metricsEnabled()) {
            return;
        }

        Log::info('observability.metric', [
            'name' => $name,
            'value' => $value,
            'dimensions' => $dimensions,
            'recorded_at' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function startSpan(string $name, array $context = []): ObservabilitySpanData
    {
        $span = new ObservabilitySpanData(
            name: $name,
            traceId: (string) ($context['trace_id'] ?? Str::ulid()),
            spanId: (string) Str::ulid(),
            startedAt: CarbonImmutable::now(),
            context: $context,
        );

        if ($this->tracingEnabled()) {
            Log::info('observability.trace.start', [
                'name' => $span->name,
                'trace_id' => $span->traceId,
                'span_id' => $span->spanId,
                'context' => $span->context,
                'started_at' => $span->startedAt->toIso8601String(),
            ]);
        }

        return $span;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function finishSpan(ObservabilitySpanData $span, array $context = []): void
    {
        if (! $this->tracingEnabled()) {
            return;
        }

        $finishedAt = CarbonImmutable::now();

        Log::info('observability.trace.finish', [
            'name' => $span->name,
            'trace_id' => $span->traceId,
            'span_id' => $span->spanId,
            'duration_ms' => $span->startedAt->diffInMilliseconds($finishedAt),
            'context' => $context,
            'finished_at' => $finishedAt->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function failSpan(ObservabilitySpanData $span, array $context = []): void
    {
        if (! $this->tracingEnabled()) {
            return;
        }

        $finishedAt = CarbonImmutable::now();

        Log::warning('observability.trace.fail', [
            'name' => $span->name,
            'trace_id' => $span->traceId,
            'span_id' => $span->spanId,
            'duration_ms' => $span->startedAt->diffInMilliseconds($finishedAt),
            'context' => $context,
            'finished_at' => $finishedAt->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnostics(): array
    {
        return [
            'enabled' => $this->enabled(),
            'metrics_enabled' => $this->metricsEnabled(),
            'tracing_enabled' => $this->tracingEnabled(),
            'log_channel' => (string) config('observability.log_channel', config('logging.default')),
        ];
    }

    private function enabled(): bool
    {
        return (bool) config('observability.enabled', true);
    }

    private function metricsEnabled(): bool
    {
        return $this->enabled() && (bool) config('observability.metrics.enabled', true);
    }

    private function tracingEnabled(): bool
    {
        return $this->enabled() && (bool) config('observability.tracing.enabled', true);
    }
}
