<?php

return [
    'name' => getenv('APP_NAME') ?: 'SewaAja',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    'url' => getenv('APP_URL') ?: 'http://localhost/sewaaja',
];

