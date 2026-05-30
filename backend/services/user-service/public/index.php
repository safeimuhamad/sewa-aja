<?php

require_once __DIR__ . '/../../../shared/Http/Response.php';

use Shared\Http\Response;

Response::json([
    'service' => 'user-service',
    'status' => 'ok',
]);

