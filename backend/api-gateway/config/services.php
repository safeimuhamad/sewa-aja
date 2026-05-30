<?php

return [
    'auth' => getenv('AUTH_SERVICE_URL') ?: 'http://localhost/sewaaja/backend/services/auth-service/public',
    'user' => getenv('USER_SERVICE_URL') ?: 'http://localhost/sewaaja/backend/services/user-service/public',
    'product' => getenv('PRODUCT_SERVICE_URL') ?: 'http://localhost/sewaaja/backend/services/product-service/public',
    'booking' => getenv('BOOKING_SERVICE_URL') ?: 'http://localhost/sewaaja/backend/services/booking-service/public',
    'payment' => getenv('PAYMENT_SERVICE_URL') ?: 'http://localhost/sewaaja/backend/services/payment-service/public',
];

