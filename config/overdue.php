<?php

return [
    'url' => env('DJANGO_URL', 'http://127.0.0.1:8001'),
    'key' => env('DJANGO_SERVICE_KEY'),
    'timeout' => (int) env('DJANGO_TIMEOUT', 10),
    // Longer limit for status changes: Django may be cold-starting on free hosting.
    'transition_timeout' => (int) env('DJANGO_TRANSITION_TIMEOUT', 60),
];
