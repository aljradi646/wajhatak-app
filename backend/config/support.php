<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Support-ticket attachment storage
    |--------------------------------------------------------------------------
    |
    | Keep report evidence on a private disk. The default local disk is useful
    | for development; production can select a private S3-compatible disk via
    | SUPPORT_TICKET_DISK without changing controller code.
    |
    */
    'tickets' => [
        'disk' => env('SUPPORT_TICKET_DISK', 'local'),
    ],
];
