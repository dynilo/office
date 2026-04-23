<?php

namespace App\Application\Integrations\Contracts;

use App\Application\Integrations\Data\IntegrationDescriptorData;
use App\Application\Integrations\Data\IntegrationRequestData;
use App\Application\Integrations\Data\IntegrationResponseData;

interface ExternalIntegrationGateway
{
    public function connector(string $name): IntegrationConnector;

    public function dispatch(IntegrationRequestData $request): IntegrationResponseData;

    /**
     * @return array<int, IntegrationDescriptorData>
     */
    public function descriptors(): array;
}
