<?php

use App\Application\Integrations\Contracts\ExternalIntegrationGateway;
use App\Application\Integrations\Data\IntegrationRequestData;
use App\Support\Exceptions\InvalidStateException;

beforeEach(function (): void {
    config()->set('integrations.connectors', [
        'stub_slack' => [
            'driver' => 'stub_slack',
            'label' => 'Stub Slack',
            'enabled' => true,
            'default_channel' => 'ops-room',
        ],
    ]);
});

it('binds the external integration gateway through the container', function (): void {
    $gateway = app(ExternalIntegrationGateway::class);
    $descriptors = $gateway->descriptors();

    expect($descriptors)->toHaveCount(1)
        ->and($descriptors[0]->name)->toBe('stub_slack')
        ->and($descriptors[0]->label)->toBe('Stub Slack')
        ->and($descriptors[0]->capabilities)->toBe(['messages.send', 'channels.list'])
        ->and($descriptors[0]->enabled)->toBeTrue();
});

it('dispatches deterministic stub slack message sends through the gateway', function (): void {
    $response = app(ExternalIntegrationGateway::class)->dispatch(new IntegrationRequestData(
        connector: 'stub_slack',
        operation: 'messages.send',
        payload: [
            'text' => 'Ship the deployment report.',
        ],
    ));

    expect($response->success)->toBeTrue()
        ->and($response->connector)->toBe('stub_slack')
        ->and($response->operation)->toBe('messages.send')
        ->and($response->data['channel'])->toBe('ops-room')
        ->and($response->data['text'])->toBe('Ship the deployment report.')
        ->and($response->data['message_id'])->toBe('stub_slack_511e6ded41c8')
        ->and($response->meta)->toBe([
            'stub' => true,
            'driver' => 'slack',
        ]);
});

it('lists stub slack channels deterministically', function (): void {
    $response = app(ExternalIntegrationGateway::class)->dispatch(new IntegrationRequestData(
        connector: 'stub_slack',
        operation: 'channels.list',
    ));

    expect($response->success)->toBeTrue()
        ->and($response->data['channels'])->toBe(['ops-room']);
});

it('rejects unknown connectors', function (): void {
    expect(fn () => app(ExternalIntegrationGateway::class)->dispatch(new IntegrationRequestData(
        connector: 'unknown',
        operation: 'messages.send',
        payload: ['text' => 'ignored'],
    )))->toThrow(InvalidStateException::class, 'External integration connector [unknown] is not supported.');
});

it('rejects unsupported operations on the stub connector', function (): void {
    expect(fn () => app(ExternalIntegrationGateway::class)->dispatch(new IntegrationRequestData(
        connector: 'stub_slack',
        operation: 'files.upload',
    )))->toThrow(
        InvalidStateException::class,
        'External integration operation [files.upload] is not supported by [stub_slack].',
    );
});

it('rejects dispatch through a disabled connector', function (): void {
    config()->set('integrations.connectors.stub_slack.enabled', false);

    expect(fn () => app(ExternalIntegrationGateway::class)->dispatch(new IntegrationRequestData(
        connector: 'stub_slack',
        operation: 'channels.list',
    )))->toThrow(
        InvalidStateException::class,
        'External integration connector [stub_slack] is disabled.',
    );
});
