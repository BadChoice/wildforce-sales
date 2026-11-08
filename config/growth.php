<?php

return [
    'discovery' => [
        'provider' => env('GROWTH_DISCOVERY_PROVIDER', 'openai'),
        'model' => env('GROWTH_DISCOVERY_MODEL', 'gpt-6-luna'),
        'qualified_score' => 70,
        'timeout' => 90,
    ],

    'enrichment' => [
        'provider' => env('GROWTH_ENRICHMENT_PROVIDER', 'openai'),
        'model' => env('GROWTH_ENRICHMENT_MODEL', 'gpt-6-luna'),
        'timeout' => 90,
    ],

    'outreach' => [
        'provider' => env('GROWTH_OUTREACH_PROVIDER', 'openai'),
        'model' => env('GROWTH_OUTREACH_MODEL', 'gpt-6-luna'),
        'timeout' => 90,
    ],

    'reply' => [
        'provider' => env('GROWTH_REPLY_PROVIDER', 'openai'),
        'model' => env('GROWTH_REPLY_MODEL', 'gpt-6-luna'),
        'timeout' => 90,
    ],
];
