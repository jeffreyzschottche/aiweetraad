<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI answer generation
    |--------------------------------------------------------------------------
    | When disabled, the StubDriver is used for every model regardless of the
    | model's configured provider. Flip AI_GENERATION_ENABLED=true (and supply
    | the relevant API keys) to generate real answers. Generated answers are
    | persisted to the `answers` table, so they are effectively cached: a
    | question is only ever generated once (unless force-regenerated).
    */
    'generation_enabled' => env('AI_GENERATION_ENABLED', false),
    'allow_stub_fallback' => env('AI_ALLOW_STUB_FALLBACK', false),

    // Hard cap so a runaway seed can never burn a fortune in one run.
    'max_generations_per_run' => env('AI_MAX_GENERATIONS_PER_RUN', 200),

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => '2023-06-01',
        'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-opus-4-8'),
        'max_tokens' => 1024,
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
        'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-5.4-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 900),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-2.5-flash'),
        'max_tokens' => env('GEMINI_MAX_TOKENS', 900),
    ],

    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'default_model' => env('DEEPSEEK_DEFAULT_MODEL', 'deepseek-chat'),
        'max_tokens' => env('DEEPSEEK_MAX_TOKENS', 900),
    ],

    'grok' => [
        'key' => env('XAI_API_KEY'),
        'base_url' => env('XAI_BASE_URL', 'https://api.x.ai/v1'),
        'default_model' => env('XAI_DEFAULT_MODEL', 'grok-4.3'),
        'max_tokens' => env('XAI_MAX_TOKENS', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider budget router
    |--------------------------------------------------------------------------
    | Credits are deliberately configuration-driven: providers expose billing
    | and usage dashboards differently, so the app keeps a local estimate per
    | day and refuses providers whose configured credit would be exceeded.
    | Prices are USD per 1M input/output tokens.
    */
    'expected_output_tokens' => env('AI_EXPECTED_OUTPUT_TOKENS', 700),
    'fallback_order' => ['openai', 'gemini', 'deepseek', 'grok', 'claude'],
    'providers' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'credit_usd' => env('AI_OPENAI_CREDIT_USD', 0),
            'spent_today_usd' => env('AI_OPENAI_SPENT_TODAY_USD', 0),
            'models' => [
                ['model' => env('OPENAI_DEFAULT_MODEL', 'gpt-5.4-mini'), 'input_per_million' => 0.75, 'output_per_million' => 4.50],
                ['model' => env('OPENAI_FALLBACK_MODEL', 'gpt-5.4'), 'input_per_million' => 2.50, 'output_per_million' => 15.00],
            ],
        ],
        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'credit_usd' => env('AI_GEMINI_CREDIT_USD', 0),
            'spent_today_usd' => env('AI_GEMINI_SPENT_TODAY_USD', 0),
            'models' => [
                ['model' => env('GEMINI_DEFAULT_MODEL', 'gemini-2.5-flash'), 'input_per_million' => 0.30, 'output_per_million' => 2.50],
                ['model' => env('GEMINI_FALLBACK_MODEL', 'gemini-2.5-flash-lite'), 'input_per_million' => 0.10, 'output_per_million' => 0.40],
            ],
        ],
        'claude' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'credit_usd' => env('AI_ANTHROPIC_CREDIT_USD', 0),
            'spent_today_usd' => env('AI_ANTHROPIC_SPENT_TODAY_USD', 0),
            'models' => [
                ['model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-sonnet-4-6'), 'input_per_million' => 3.00, 'output_per_million' => 15.00],
                ['model' => env('ANTHROPIC_FALLBACK_MODEL', 'claude-haiku-4-5'), 'input_per_million' => 1.00, 'output_per_million' => 5.00],
            ],
        ],
        'deepseek' => [
            'key' => env('DEEPSEEK_API_KEY'),
            'credit_usd' => env('AI_DEEPSEEK_CREDIT_USD', 0),
            'spent_today_usd' => env('AI_DEEPSEEK_SPENT_TODAY_USD', 0),
            'models' => [
                ['model' => env('DEEPSEEK_DEFAULT_MODEL', 'deepseek-chat'), 'input_per_million' => 0.27, 'output_per_million' => 1.10],
                ['model' => env('DEEPSEEK_FALLBACK_MODEL', 'deepseek-reasoner'), 'input_per_million' => 0.55, 'output_per_million' => 2.19],
            ],
        ],
        'grok' => [
            'key' => env('XAI_API_KEY'),
            'credit_usd' => env('AI_XAI_CREDIT_USD', 0),
            'spent_today_usd' => env('AI_XAI_SPENT_TODAY_USD', 0),
            'models' => [
                ['model' => env('XAI_DEFAULT_MODEL', 'grok-4.3'), 'input_per_million' => 1.25, 'output_per_million' => 2.50],
            ],
        ],
    ],
];
