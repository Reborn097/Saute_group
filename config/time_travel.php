<?php

return [
    'enabled' => env('APP_TIME_TRAVEL_ENABLED', false),
    'date'    => env('APP_TIME_TRAVEL_DATE', null),
    'tz'      => env('APP_TIME_TRAVEL_TZ', env('APP_TIMEZONE', 'UTC')),
];