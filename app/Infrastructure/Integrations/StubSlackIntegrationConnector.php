<?php

namespace App\Infrastructure\Integrations;

use App\Application\Integrations\Contracts\IntegrationConnector;
use App\Application\Integrations\Data\IntegrationDescriptorData;
use App\Application\Integrations\Data\IntegrationRequestData;
use App\Application\Integrations\Data\IntegrationResponseData;
use App\Support\Exceptions\InvalidStateException;
use InvalidArgumentException;

final readonly class StubSlackIntegrationConnector implements IntegrationConnector
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private array $config,
    ) {}

    public function descriptor(): IntegrationDescriptorData
    {
        return new IntegrationDescriptorData(
            name: 'stub_slack',
            label: (string) ($this->config['label'] ?? 'Stub Slack'),
            capabilities: ['messages.send', 'channels.list'],
            enabled: (bool) ($this->config['enabled'] ?? true),
        );
    }

    public function handle(IntegrationRequestData $request): IntegrationResponseData
    {
        return match ($request->operation) {
            'messages.send' => $this->handleMessageSend($request),
            'channels.list' => $this->handleChannelList($request),
            default => throw new InvalidStateException(
                "External integration operation [{$request->operation}] is not supported by [stub_slack].",
            ),
        };
    }

    private function handleMessageSend(IntegrationRequestData $request): IntegrationResponseData
    {
        $text = trim((string) ($request->payload['text'] ?? ''));

        if ($text === '') {
            throw new InvalidArgumentException('Stub Slack message text cannot be empty.');
        }

        $channel = (string) ($request->payload['channel'] ?? $this->defaultChannel());
        $messageId = 'stub_slack_'.substr(sha1($channel.'|'.$text), 0, 12);

        return new IntegrationResponseData(
            connector: 'stub_slack',
            operation: 'messages.send',
            success: true,
            data: [
                'message_id' => $messageId,
                'channel' => $channel,
                'text' => $text,
            ],
            meta: [
                'stub' => true,
                'driver' => 'slack',
            ],
        );
    }

    private function handleChannelList(IntegrationRequestData $request): IntegrationResponseData
    {
        $channel = (string) ($request->payload['channel'] ?? $this->defaultChannel());

        return new IntegrationResponseData(
            connector: 'stub_slack',
            operation: 'channels.list',
            success: true,
            data: [
                'channels' => [$channel],
            ],
            meta: [
                'stub' => true,
                'driver' => 'slack',
            ],
        );
    }

    private function defaultChannel(): string
    {
        return (string) ($this->config['default_channel'] ?? 'office-ops');
    }
}
