<?php

declare(strict_types=1);

use Rasuvaeff\Yii3SettingsUi\SettingsRoutes;

return [
    SettingsRoutes::PARAM_KEY => [
        'route_prefix' => '/admin/settings',
        'layout' => null,
        'views' => [],
        // Per-slot middleware. Available keys:
        //   all    – prepended to every route (auth, logging, etc.)
        //   list, edit – GET routes
        //   update, reset – POST routes
        // RequestBodyParser is added automatically to POST routes (update, reset).
        // Set 'body_parser' => false to disable if your pipeline already applies it globally.
        'middlewares' => [],
        'body_parser' => true,
        // Route names used by SettingsUrls for link/redirect generation.
        // Override individual keys only when your app uses a different naming convention.
        'route_names' => [
            'list' => SettingsRoutes::LIST,
            'edit' => SettingsRoutes::EDIT,
            'update' => SettingsRoutes::UPDATE,
            'reset' => SettingsRoutes::RESET,
        ],
    ],
];
