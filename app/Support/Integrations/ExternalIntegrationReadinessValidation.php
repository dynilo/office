<?php

namespace App\Support\Integrations;

use App\Application\Integrations\Contracts\ExternalIntegrationGateway;
use App\Application\Integrations\Data\IntegrationDescriptorData;
use Throwable;

final readonly class ExternalIntegrationReadinessValidation
{
    public function __construct(
        private ExternalIntegrationGateway $gateway,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $configuredConnectors = (array) config('integrations.connectors', []);
        $defaultConnector = (string) config('integrations.default', '');
        $allowStubFallbackInProduction = (bool) config('integrations.allow_stub_fallback_in_production', false);
        $environment = (string) config('app.env');

        $runtime = [
            'default_connector' => $defaultConnector,
            'configured_connector_names' => array_keys($configuredConnectors),
            'enabled_connector_names' => [],
            'descriptor_names' => [],
            'descriptor_capabilities' => [],
            'unsupported_drivers' => [],
            'stub_only_mode' => false,
            'gateway_error' => null,
        ];

        $checks = [
            'default_connector_configured' => $defaultConnector !== '',
            'connectors_configured' => $configuredConnectors !== [],
            'configured_drivers_supported' => true,
            'gateway_descriptors_resolvable' => false,
            'default_connector_exists' => false,
            'default_connector_enabled' => false,
            'enabled_connector_present' => false,
            'enabled_connectors_have_capabilities' => false,
            'stub_only_mode_allowed' => true,
        ];

        foreach ($configuredConnectors as $name => $connectorConfig) {
            $driver = $connectorConfig['driver'] ?? null;

            if (! in_array($driver, ['stub_slack'], true)) {
                $runtime['unsupported_drivers'][] = [
                    'name' => $name,
                    'driver' => $driver,
                ];
            }

            if (($connectorConfig['enabled'] ?? false) === true) {
                $runtime['enabled_connector_names'][] = $name;
            }
        }

        $checks['configured_drivers_supported'] = $runtime['unsupported_drivers'] === [];
        $checks['enabled_connector_present'] = $runtime['enabled_connector_names'] !== [];
        $checks['default_connector_exists'] = array_key_exists($defaultConnector, $configuredConnectors);
        $checks['default_connector_enabled'] = $checks['default_connector_exists']
            && (($configuredConnectors[$defaultConnector]['enabled'] ?? false) === true);

        if (! $checks['connectors_configured'] || ! $checks['configured_drivers_supported']) {
            return $this->buildReport($environment, $runtime, $checks, $allowStubFallbackInProduction);
        }

        try {
            $descriptors = $this->gateway->descriptors();

            $runtime['descriptor_names'] = array_map(
                static fn (IntegrationDescriptorData $descriptor): string => $descriptor->name,
                $descriptors,
            );
            $runtime['descriptor_capabilities'] = array_reduce(
                $descriptors,
                static function (array $carry, IntegrationDescriptorData $descriptor): array {
                    $carry[$descriptor->name] = $descriptor->capabilities;

                    return $carry;
                },
                [],
            );
            $runtime['stub_only_mode'] = $descriptors !== []
                && collect($runtime['descriptor_names'])->every(static fn (string $name): bool => $name === 'stub_slack');

            $checks['gateway_descriptors_resolvable'] = true;
            $checks['enabled_connectors_have_capabilities'] = collect($descriptors)
                ->filter(static fn (IntegrationDescriptorData $descriptor): bool => $descriptor->enabled)
                ->every(static fn (IntegrationDescriptorData $descriptor): bool => $descriptor->capabilities !== []);
            $checks['stub_only_mode_allowed'] = ! $runtime['stub_only_mode']
                || $environment !== 'production'
                || $allowStubFallbackInProduction;
        } catch (Throwable $exception) {
            $runtime['gateway_error'] = [
                'message' => $exception->getMessage(),
                'type' => $exception::class,
            ];
        }

        return $this->buildReport($environment, $runtime, $checks, $allowStubFallbackInProduction);
    }

    /**
     * @param  array<string, mixed>  $runtime
     * @param  array<string, bool>  $checks
     * @return array<string, mixed>
     */
    private function buildReport(
        string $environment,
        array $runtime,
        array $checks,
        bool $allowStubFallbackInProduction,
    ): array {
        return [
            'environment' => $environment,
            'runtime' => $runtime,
            'checks' => $checks,
            'fallback' => [
                'safe' => true,
                'stub_connector_available' => in_array('stub_slack', $runtime['configured_connector_names'], true),
                'stub_only_mode_allowed_in_production' => $allowStubFallbackInProduction,
                'disabled_connectors_reject_dispatch' => true,
            ],
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
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
