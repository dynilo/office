<?php

namespace App\Application\Integrations\Services;

use App\Application\Integrations\Contracts\ExternalIntegrationGateway;
use App\Application\Integrations\Contracts\IntegrationConnector;
use App\Application\Integrations\Data\IntegrationDescriptorData;
use App\Application\Integrations\Data\IntegrationRequestData;
use App\Application\Integrations\Data\IntegrationResponseData;
use App\Support\Exceptions\InvalidStateException;

final readonly class ExternalIntegrationGatewayService implements ExternalIntegrationGateway
{
    /**
     * @param  array<string, IntegrationConnector>  $connectors
     */
    public function __construct(
        private array $connectors,
    ) {}

    public function connector(string $name): IntegrationConnector
    {
        $connector = $this->connectors[$name] ?? null;

        if (! $connector instanceof IntegrationConnector) {
            throw new InvalidStateException("External integration connector [{$name}] is not supported.");
        }

        if (! $connector->descriptor()->enabled) {
            throw new InvalidStateException("External integration connector [{$name}] is disabled.");
        }

        return $connector;
    }

    public function dispatch(IntegrationRequestData $request): IntegrationResponseData
    {
        return $this->connector($request->connector)->handle($request);
    }

    public function descriptors(): array
    {
        return array_values(array_map(
            static fn (IntegrationConnector $connector): IntegrationDescriptorData => $connector->descriptor(),
            $this->connectors,
        ));
    }
}
