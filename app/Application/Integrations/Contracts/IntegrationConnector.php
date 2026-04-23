<?php

namespace App\Application\Integrations\Contracts;

use App\Application\Integrations\Data\IntegrationDescriptorData;
use App\Application\Integrations\Data\IntegrationRequestData;
use App\Application\Integrations\Data\IntegrationResponseData;

interface IntegrationConnector
{
    public function descriptor(): IntegrationDescriptorData;

    public function handle(IntegrationRequestData $request): IntegrationResponseData;
}
