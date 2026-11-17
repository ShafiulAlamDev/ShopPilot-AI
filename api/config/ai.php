<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key'         => env('OPENAI_API_KEY'),
        'model'           => env('OPENAI_MODEL', 'gpt-4o'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'timeout'         => 60,
        'max_retries'     => 3,
        'retry_delay'     => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'product_description_generation' => true,
        'semantic_search'                => true,
        'shopping_assistant'             => true,
        'product_recommendations'        => true,
        'product_comparison'             => true,
        'customer_support'               => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Limits (per organization per month)
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'free'       => ['requests' => 100,  'tokens' => 50000],
        'starter'    => ['requests' => 1000, 'tokens' => 500000],
        'pro'        => ['requests' => 5000, 'tokens' => 2500000],
        'enterprise' => ['requests' => null, 'tokens' => null], // unlimited
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Token Budgets
    |--------------------------------------------------------------------------
    */
    'token_budgets' => [
        'product_description' => 2000,
        'shopping_assistant'  => 4000,
        'semantic_search'     => 1000,
        'comparison'          => 3000,
        'support'             => 2000,
    ],
];
