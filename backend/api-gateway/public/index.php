<?php

require_once __DIR__ . '/../../shared/Http/Response.php';

use Shared\Http\Response;

Response::json([
    'service' => 'api-gateway',
    'status' => 'ok',
    'message' => 'SewaAja API Gateway is running.',
]);

