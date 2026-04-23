<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('exposes external integration readiness through a console command', function (): void {
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

    expect(Artisan::call('integrations:validate-runtime'))->toBe(0);

    $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($output['ready'])->toBeTrue()
        ->and($output['checks']['default_connector_enabled'])->toBeTrue()
        ->and($output['runtime']['stub_only_mode'])->toBeTrue();
});

it('documents external integration production expectations', function (): void {
    $document = File::get(base_path('docs/EXTERNAL_INTEGRATIONS_PRODUCTION.md'));

    expect($document)->toContain('php artisan integrations:validate-runtime')
        ->toContain('stub-only mode')
        ->toContain('INTEGRATIONS_ALLOW_STUB_FALLBACK_IN_PRODUCTION');
});
