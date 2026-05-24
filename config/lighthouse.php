<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GraphQL Schema
    |--------------------------------------------------------------------------
    */
    'schema' => [
        'register' => base_path('graphql/schema.graphql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    | The /graphql route is registered by Lighthouse using this config.
    | graphql.key runs first (machine-to-machine auth via API key + secret).
    | auth:sanctum follows as a soft guard — Lighthouse's @guard and @auth
    | directives will reject unauthenticated requests at the field level.
    */
    // graphql.key handles both M2M (API key) and falls through for browser
    // sessions. auth:sanctum must NOT be here — it would 401 API key callers
    // before graphql.key sets the user. Field-level auth is handled by the
    // @guard and @auth directives in the schema instead.
    'route' => [
        'uri'        => '/graphql',
        'name'       => 'graphql',
        'middleware' => [
            'graphql.key',
        ],
        'prefix'     => '',
        'domain'     => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Guard
    |--------------------------------------------------------------------------
    */
    'guard' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | Introspection
    |--------------------------------------------------------------------------
    | Enable in dev so the central Next.js app can inspect the schema.
    | Disable in production.
    */
    'introspection' => env('LIGHTHOUSE_INTROSPECTION', true),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_count' => 15,
        'max_count'     => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    */
    'namespaces' => [
        'models'     => ['TrackAnyDevice\\Core\\Models', 'GraphQlApp\\Models'],
        'queries'    => 'GraphQlApp\\GraphQL\\Queries',
        'mutations'  => 'GraphQlApp\\GraphQL\\Mutations',
        'subscriptions' => 'GraphQlApp\\GraphQL\\Subscriptions',
        'interfaces' => 'GraphQlApp\\GraphQL\\Interfaces',
        'unions'     => 'GraphQlApp\\GraphQL\\Unions',
        'scalars'    => 'GraphQlApp\\GraphQL\\Scalars',
        'directives' => ['GraphQlApp\\GraphQL\\Directives'],
        'validators' => ['GraphQlApp\\GraphQL\\Validators'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'max_query_complexity'  => 200,
        'max_query_depth'       => 15,
        // 0 = introspection enabled, 1 = disabled. Must be int, not bool.
        'disable_introspection' => (int) env('LIGHTHOUSE_DISABLE_INTROSPECTION', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enable' => env('LIGHTHOUSE_CACHE_ENABLE', ! env('APP_DEBUG', false)),
        'store'  => env('LIGHTHOUSE_CACHE_STORE', 'default'),
        'key'    => 'lighthouse-schema',
        'ttl'    => null,
        'tags'   => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Cache
    |--------------------------------------------------------------------------
    */
    'query_cache' => [
        // Disabled — serialising DocumentNode to Redis causes __PHP_Incomplete_Class
        // errors when the class definition changes between requests/deploys.
        'enable' => env('LIGHTHOUSE_QUERY_CACHE_ENABLE', false),
        'store'  => env('LIGHTHOUSE_QUERY_CACHE_STORE', env('CACHE_STORE', 'redis')),
        'ttl'    => 24 * 60 * 60,
        'tags'   => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing (disabled)
    |--------------------------------------------------------------------------
    */
    'tracing' => [
        'enabled'  => false,
        'driver'   => \Nuwave\Lighthouse\Tracing\FederatedTracing\FederatedTracing::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batched queries (allow)
    |--------------------------------------------------------------------------
    */
    'batched_queries' => true,

    /*
    |--------------------------------------------------------------------------
    | Parse source location
    |--------------------------------------------------------------------------
    */
    'parse_source_location' => true,

    /*
    |--------------------------------------------------------------------------
    | Error handlers
    |--------------------------------------------------------------------------
    */
    'error_handlers' => [
        \Nuwave\Lighthouse\Execution\AuthorizationErrorHandler::class,
        \Nuwave\Lighthouse\Execution\AuthenticationErrorHandler::class,
        \Nuwave\Lighthouse\Execution\ReportingErrorHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global ID
    |--------------------------------------------------------------------------
    */
    'global_id_field' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Subscriptions (disabled for now)
    |--------------------------------------------------------------------------
    */
    'subscriptions' => [
        'storage'                 => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE', 'redis'),
        'storage_ttl'             => env('LIGHTHOUSE_SUBSCRIPTION_STORAGE_TTL', null),
        'broadcaster'             => env('LIGHTHOUSE_BROADCASTER', 'pusher'),
        'broadcaster_echo'        => false,
        'exclude_empty'           => true,
        'queue_broadcasts'        => env('LIGHTHOUSE_QUEUE_BROADCASTS', false),
        'broadcasts_queue_name'   => env('LIGHTHOUSE_BROADCASTS_QUEUE_NAME', null),
        'broadcasts_queue_connection' => env('LIGHTHOUSE_BROADCASTS_QUEUE_CONNECTION', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Defer (disabled)
    |--------------------------------------------------------------------------
    */
    'defer' => [
        'enabled'   => false,
        'max_nested_fields' => 0,
    ],
];
