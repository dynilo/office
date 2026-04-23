<?php

use App\Support\Integrations\ExternalIntegrationReadinessValidation;

it('reports integration readiness as ready in non-production stub mode', function (): void {
    config()->set('app.env', 'testing');
    config()->set('integrations.default', 'stub_slack');
    config()->set('integrations.allow_stub_fallback_in_production', false);
    config()->set('integrations.connectors', [
        'stub_slack' => [
            'driver' => 'stub_slack',
            'label' => 'Stub Slack',
            'enabled' => true,
            'default_channel' => 'ops-room',
        ],
    ]);

    $report = app(ExternalIntegrationReadinessValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['checks']['configured_drivers_supported'])->toBeTrue()
        ->and($report['checks']['enabled_connector_present'])->toBeTrue()
        ->and($report['checks']['stub_only_mode_allowed'])->toBeTrue();
});

it('fails safely in production when only stub integrations are enabled without explicit fallback opt in', function (): void {
    config()->set('app.env', 'production');
    config()->set('integrations.default', 'stub_slack');
    config()->set('integrations.allow_stub_fallback_in_production', false);
    config()->set('integrations.connectors', [
        'stub_slack' => [
            'driver' => 'stub_slack',
            'label' => 'Stub Slack',
            'enabled' => true,
            'default_channel' => 'ops-room',
        ],
    ]);

    $report = app(ExternalIntegrationReadinessValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['runtime']['stub_only_mode'])->toBeTrue()
        ->and($report['checks']['stub_only_mode_allowed'])->toBeFalse()
        ->and($report['fallback']['safe'])->toBeTrue()
        ->and($report['unavailable_reason'])->toBe('stub_only_mode_allowed');
});
