<?php

use Fruitcake\Cors\CorsServiceProvider;

return [
    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
    ],
    'providers' => [
        CorsServiceProvider::class,
    ],
    'cachePath' => realpath(storage_path('framework/cache'))
];
