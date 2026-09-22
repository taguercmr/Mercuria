<?php

return [

    'name' => env('APP_NAME', 'Mercuria'),

    'mercuriale_year' => 2026,
    'mercuriale_source' => 'MINCOMMERCE / DMQP — SYGEP Budget 2026',

    /*
    |--------------------------------------------------------------------------
    | Freemium defaults
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'free' => [
            'code' => 'free',
            'name' => 'Gratuit',
            'documents_per_month' => (int) env('MERCURIA_FREE_DOCS_PER_MONTH', 10),
            'clients_limit' => (int) env('MERCURIA_FREE_CLIENTS_LIMIT', 25),
            'api_access' => false,
            'team_members' => 1,
        ],
        'pro' => [
            'code' => 'pro',
            'name' => 'Pro',
            'documents_per_month' => null, // unlimited
            'clients_limit' => null,
            'api_access' => true,
            'team_members' => (int) env('MERCURIA_PRO_TEAM_MEMBERS', 10),
            'price_monthly_xaf' => (int) env('MERCURIA_PRO_PRICE_MONTHLY', 15000),
            'price_yearly_xaf' => (int) env('MERCURIA_PRO_PRICE_YEARLY', 150000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance (DB-driven, toggled from Admin)
    |--------------------------------------------------------------------------
    */
    'maintenance' => [
        'bypass_roles' => ['platform_admin'],
        'default_message' => 'Mercuria est temporairement en maintenance. Merci de réessayer dans quelques minutes.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Recherche catalogue
    |--------------------------------------------------------------------------
    | driver: auto | sql | meilisearch
    | auto = Meilisearch si SCOUT_DRIVER=meilisearch + host, sinon SQL
    */
    'search' => [
        'driver' => env('MERCURIA_SEARCH_DRIVER', 'auto'),
    ],

];
