<?php

declare(strict_types=1);

return [
    'operator_password' => env('SEED_OPERATOR_PASSWORD', 'local-operator-password'),
    'planner_password' => env('SEED_PLANNER_PASSWORD', 'local-planner-password'),
    'warehouse_client_secret' => env('WAREHOUSE_CLIENT_SECRET', 'local-warehouse-client-secret'),
    'driver_client_secret' => env('DRIVER_CLIENT_SECRET', 'local-driver-client-secret'),
    'pda_client_secret' => env('PDA_CLIENT_SECRET', 'local-pda-client-secret'),
    'partner_client_secret' => env('PARTNER_CLIENT_SECRET', 'local-partner-client-secret'),
];
