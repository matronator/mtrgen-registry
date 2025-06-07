<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD'],
    'allowed_origins' => ['https://mtrgen.com', 'http://localhost:*', 'http://mtrgen-api.matronator.cz', 'http://factory.matronator.cz', 'https://mtrgen.matronator.cz', 'https://matronator.cz'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => false,
    'max_age' => false,
    'supports_credentials' => false,
];
