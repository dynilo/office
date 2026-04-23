<?php

return [
    'default' => env('INTEGRATIONS_DEFAULT', 'stub_slack'),

    'connectors' => [
        'stub_slack' => [
            'driver' => 'stub_slack',
            'label' => env('INTEGRATIONS_STUB_SLACK_LABEL', 'Stub Slack'),
            'enabled' => env('INTEGRATIONS_STUB_SLACK_ENABLED', true),
            'default_channel' => env('INTEGRATIONS_STUB_SLACK_CHANNEL', 'office-ops'),
        ],
    ],
];
