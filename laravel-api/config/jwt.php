<?php

return [
    'secret' => env('JWT_SECRET', 'your-super-secret-jwt-key'),
    'ttl' => env('JWT_TTL', 10080), // 7 days in minutes
    'algo' => 'HS256',
];