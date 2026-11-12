<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe API credentials
    |--------------------------------------------------------------------------
    */
    'secret' => env('STRIPE_SECRET'),
    'key' => env('STRIPE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    | Used when creating PaymentIntents and when a connected account's
    | country doesn't support the preferred currency below.
    */
    'default_currency' => env('STRIPE_DEFAULT_CURRENCY', 'usd'),
    'preferred_connect_currency' => env('STRIPE_PREFERRED_CONNECT_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Country spec cache
    |--------------------------------------------------------------------------
    | Stripe's CountrySpec resolution never changes within a deploy, so it's
    | cached. This is reference data with no app-owned write path, so a bare
    | TTL is an acceptable invalidation story (see package README).
    */
    'country_spec_cache_ttl' => env('STRIPE_COUNTRY_SPEC_CACHE_TTL', 86400),
    'cache_store' => env('STRIPE_TOOLKIT_CACHE_STORE', null), // null = default store

    /*
    |--------------------------------------------------------------------------
    | Host-app bindings
    |--------------------------------------------------------------------------
    | The toolkit never references your app's models/repositories directly.
    | Bind your own implementations of the package's contracts here (FQCNs),
    | and the service provider resolves them out of the container.
    */
    'bindings' => [
        \Kareemaladawy\StripeConnectToolkit\Contracts\CountryNameResolver::class => null,
        \Kareemaladawy\StripeConnectToolkit\Contracts\AccountOwnerResolver::class => null,
        \Kareemaladawy\StripeConnectToolkit\Contracts\ExternalAccountSyncHandler::class => null,
    ],
];
