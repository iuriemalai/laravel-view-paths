<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Paths
    |--------------------------------------------------------------------------
    |
    | List of additional paths that should be registered with the view finder.
    | These will be prepended to existing view paths, giving them precedence.
    | Disable this action by leaving the 'paths' array empty.
    |
    */
    'paths' => [
        base_path('resources/views_peak'),
        // base_path('resources/views_starters/laravel/livewire-starter-kit'),
        // base_path('vendor/laraveldaily/starter-kit/resources/views'),
        // Example: resource_path('views/theme_views'),
        // Example: storage_path('custom/another/path'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaced View Paths
    |--------------------------------------------------------------------------
    |
    | List of additional namespaced paths to register with the view finder.
    | The key is the namespace, and the value is the path to the views.
    | Special handling is provided for 'volt-livewire' which will be mounted
    | with Livewire Volt instead of being registered as a view namespace.
    |
    */
    'namespaced_paths' => [
        'statamic' => base_path('resources/views_peak/vendor/statamic'),
        // 'statamic-peak-seo' => base_path('resources/views_peak/vendor/statamic-peak-seo'),
        // 'volt-livewire' => base_path('resources/views_starters/laravel/livewire-starter-kit/livewire'),
        // 'flux' => base_path('resources/views_starters/laravel/livewire-starter-kit/flux'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable View Paths Caching
    |--------------------------------------------------------------------------
    |
    | Enable or disable caching for the view paths.
    | When disabled, the paths will be loaded fresh on every request, without
    | storing or retrieving from the cache. Useful during development.
    |
    */
    'cache_enabled' => config('VIEW_PATHS_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | Define how long the additional view paths should be cached.
    |
    | Supported formats:
    | - an integer for seconds (ex: 15)
    | - a string ending in:
    |     - "s" for seconds (ex: "15s")
    |     - "m" for minutes (ex: "30m")
    |     - "h" for hours (ex: "2h")
    |     - "d" for days (ex: "1d")
    |     - "w" for weeks (ex: "2w")
    |     - "M" for months (ex: "1M")
    |     - "y" for years (ex: "1y")
    | - the string "forever"
    |
    */
    'cache_duration' => config('VIEW_PATHS_CACHE_DURATION', 'forever'),

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | The key under which the additional view paths cache is stored.
    | Useful if you want to avoid key collisions.
    |
    */
    'cache_key' => config('VIEW_PATHS_CACHE_KEY', 'view_paths'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Settings for logging view path operations.
    | Set to false to disable logging entirely.
    |
    */
    'logging' => [
        'enabled' => config('VIEW_PATHS_LOGGING_ENABLED', false),
        'level' => config('VIEW_PATHS_LOG_LEVEL', 'info'), // 'debug', 'info', 'warning', 'error'
        'channel' => config('VIEW_PATHS_LOG_CHANNEL', null), // null uses default channel
    ],

    /*
    |--------------------------------------------------------------------------
    | View Information Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging the name and path of all views rendered on each request.
    | Useful for debugging view resolution issues and template selection.
    |
    */
    'log_views_info' => false,

    /*
    |--------------------------------------------------------------------------
    | Enable View Paths Functionality
    |--------------------------------------------------------------------------
    |
    | Set this to false to completely disable the functionality provided by the
    | View Paths package. When disabled, no additional view paths or namespaces
    | will be registered, and no view path-related logic will be executed.
    | Useful for temporarily turning off the package without uninstalling it.
    |
    */
    'enabled' => true,
];
