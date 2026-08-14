<?php

return [
    'disk' => env('ADVERTISING_DISK', 'public'),
    'path' => env('ADVERTISING_PATH', 'advertising'),
    'video_extensions' => [
        'mp4',
        'mov',
        'avi',
        'mkv',
        'webm'
    ],
    /* Published models may be replaced here with application models. */
    'models' => [
        'category' => Ahinest\LaravelAdvertising\Models\Category::class,
        'resource' => Ahinest\LaravelAdvertising\Models\Resource::class,
        'container' => Ahinest\LaravelAdvertising\Models\Container::class,
        'advertisement' => Ahinest\LaravelAdvertising\Models\Advertisement::class,
    ],
    /*
     * Claves de entrada para input. Cambie los valores
     * publicados si su aplicación usa otros nombres en formularios o API.
     *   API input aliases: external name => database/model attribute
     */
    'input_map' => [
        'category' => [
            'name' => 'name'
        ],
        'resource' => [
            // Compatibilidad con el nombre usado por otras implementaciones.
            'category' => 'category_id',
            'path' => 'path',
            'alt' => 'alt',
            'eyebrow' => 'eyebrow',
            'height' => 'height',
            'button_label' => 'button_label',
            'button_url' => 'button_url',
            'expires_at' => 'expires_at'
        ],
        'container' => [
            'name' => 'name',
            'expires_at' => 'expires_at'
        ],
        'advertisement' => [
            'name' => 'name',
            'expires_at' => 'expires_at'
        ],
    ],
    /*
     * Claves de entrada para relaciones muchos-a-muchos. Cambie los valores
     * publicados si su aplicación usa otros nombres en formularios o API.
     */
    'relation_inputs' => [
        'resource_containers' => 'containers',
        'container_resources' => 'resources',
        'advertisement_containers' => 'containers',
    ],
    /*
     * Filtros permitidos para los métodos index. Cada clave de entrada invoca
     * el scope local indicado. Puede registrar scopes de sus modelos publicados:
     * 'external_id' => 'externalId' invoca scopeExternalId($query, $value).
     */
    'index_filters' => [
        'category' => [
            'name' => 'name', 'slug' => 'slug', 'description' => 'description',
        ],
        'resource' => [
            'description' => 'description', 'expires_at' => 'expiresAt',
            'active' => 'active', 'category' => 'category', 'category_id' => 'category',
            'container' => 'container', 'container_id' => 'container',
        ],
        'container' => [
            'name' => 'name', 'slug' => 'slug', 'description' => 'description',
            'expires_at' => 'expiresAt', 'active' => 'active',
        ],
        'advertisement' => [
            'name' => 'name', 'slug' => 'slug', 'description' => 'description',
            'expires_at' => 'expiresAt', 'active' => 'active',
        ],
    ],
    'tables' => [
        'categories' => 'advertising_categories',
        'resources' => 'advertising_resources',
        'containers' => 'advertising_containers',
        'advertisements' => 'advertising_advertisements',
        'container_resource' => 'advertising_container_resource',
        'advertisement_container' => 'advertisement_container',
    ],
    'routes' => [
        'enabled' => env('ADVERTISING_ROUTES_ENABLED', true),
        'prefix' => 'advertising',
        'middleware' => ['api'],
    ],
];
