<?php

return [
    'enabled' => (bool) env('LOCAL_OPERATIONS_ENABLED', false),
    'restaurant_id' => env('LOCAL_RESTAURANT_ID'),
    'restaurant_slug' => env('LOCAL_RESTAURANT_SLUG'),
    'cloud_endpoint' => env('LOCAL_CLOUD_ENDPOINT'),
];
