<?php

return [
    'tracking_enabled' => env('COST_TRACKING_ENABLED', true),
    'currency' => env('COST_TRACKING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Provider Cost Rates
    |--------------------------------------------------------------------------
    |
    | Rates are expressed as estimated currency units per one million tokens.
    | Defaults are intentionally zero because provider pricing changes over
    | time; production environments should set these explicitly.
    |
    */
    'provider_rates' => [
        'openai_compatible' => [
            '*' => [
                'input_per_million_tokens' => (float) env('OPENAI_COST_INPUT_PER_MILLION_TOKENS', 0),
                'output_per_million_tokens' => (float) env('OPENAI_COST_OUTPUT_PER_MILLION_TOKENS', 0),
            ],
        ],
        'openai_compatible_secondary' => [
            '*' => [
                'input_per_million_tokens' => (float) env('OPENAI_SECONDARY_COST_INPUT_PER_MILLION_TOKENS', env('OPENAI_COST_INPUT_PER_MILLION_TOKENS', 0)),
                'output_per_million_tokens' => (float) env('OPENAI_SECONDARY_COST_OUTPUT_PER_MILLION_TOKENS', env('OPENAI_COST_OUTPUT_PER_MILLION_TOKENS', 0)),
            ],
        ],
    ],
];
