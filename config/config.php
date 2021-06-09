<?php

return [
    'token' => env('COREMETRICS_TOKEN'),

    'port' => env('COREMETRICS_PORT', 8089),

    'server' => [
        'base_url' => env('COREMETRICS_BASE_URL'),
    ],

    'allow-git' => env('COREMETRICS_ALLOW_GIT'),
];