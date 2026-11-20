<?php

return [
    'url' => env('DJANGO_URL', 'http://127.0.0.1:8001'),
    'key' => env('DJANGO_SERVICE_KEY'),
    'timeout' => (int) env('DJANGO_TIMEOUT', 90),
];
