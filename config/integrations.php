<?php

return [
    'default' => env('INTEGRATIONS_DEFAULT', 'stub_slack'),
    'allow_stub_fallback_in_production' => env('INTEGRATIONS_ALLOW_STUB_FALLBACK_IN_PRODUCTION', false),

    'connectors' => [
        'stub_slack' => [
            'driver' => 'stub_slack',
            'label' => env('INTEGRATIONS_STUB_SLACK_LABEL', 'Stub Slack'),
            'enabled' => env('INTEGRATIONS_STUB_SLACK_ENABLED', true),
            'default_channel' => env('INTEGRATIONS_STUB_SLACK_CHANNEL', 'office-ops'),
        ],
    ],
];
