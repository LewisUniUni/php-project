<?php

declare(strict_types=1);

return [
    'client_token_minutes' => (int) env('CLIENT_TOKEN_MINUTES', 15),

    'scopes' => [
        'inventory.read' => 'Read stock balances and reservations',
        'inventory.write' => 'Receive stock and hold or release reservations',
        'delivery.read' => 'Read delivery stop state',
        'delivery.write' => 'Change delivery stop state',
        'telemetry.write' => 'Record and read bounded device scan events',
    ],
];
