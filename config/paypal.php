<?php

return [
    'client_id' => 'XXXXXX',
    'secret' => 'XXXXXX',
    'settings' => [
        'mode' => 'sandbox',
        'http.ConnectionTimeOut' => 1000,
        'log.LogEnabled' => true,
        'log.FileName' => storage_path().'/logs/paypal.log',
        'log.LogLevel' => 'FINE',
    ],
];
