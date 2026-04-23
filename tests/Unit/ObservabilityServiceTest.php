<?php

use App\Support\Observability\ObservabilityService;
use Illuminate\Support\Facades\Log;

it('emits structured metrics and trace lifecycle logs', function (): void {
    Log::spy();

    $service = app(ObservabilityService::class);
    $span = $service->startSpan('runtime.execution', [
        'trace_id' => 'trace-fixed',
        'execution_id' => 'exec-1',
    ]);

    $service->metric('runtime.execution.transitions_total', 1, [
        'from' => 'pending',
        'to' => 'running',
    ]);
    $service->finishSpan($span, [
        'status' => 'ok',
    ]);

    Log::shouldHaveReceived('info')
        ->with(
            'observability.trace.start',
            Mockery::on(static function (array $context): bool {
                return $context['trace_id'] === 'trace-fixed'
                    && $context['name'] === 'runtime.execution'
                    && $context['context']['execution_id'] === 'exec-1';
            }),
        )
        ->once();

    Log::shouldHaveReceived('info')
        ->with(
            'observability.metric',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'runtime.execution.transitions_total'
                    && $context['value'] === 1
                    && $context['dimensions']['to'] === 'running';
            }),
        )
        ->once();

    Log::shouldHaveReceived('info')
        ->with(
            'observability.trace.finish',
            Mockery::on(static function (array $context): bool {
                return $context['trace_id'] === 'trace-fixed'
                    && $context['context']['status'] === 'ok'
                    && is_numeric($context['duration_ms']);
            }),
        )
        ->once();
});

it('reports observability diagnostics from configuration', function (): void {
    config()->set('observability.enabled', true);
    config()->set('observability.metrics.enabled', true);
    config()->set('observability.tracing.enabled', false);
    config()->set('observability.log_channel', 'stack');

    expect(app(ObservabilityService::class)->diagnostics())->toBe([
        'enabled' => true,
        'metrics_enabled' => true,
        'tracing_enabled' => false,
        'log_channel' => 'stack',
    ]);
});
