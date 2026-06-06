<?php
return [
    'default' => env('QUEUE_CONNECTION', 'redis'),
    'connections' => [
        'sync' => ['driver' => 'sync'],
        'redis' => ['driver' => 'redis', 'connection' => 'default', 'queue' => env('REDIS_QUEUE', 'wms'), 'retry_after' => 90, 'block_for' => null],
    ],
    'batching' => ['database' => env('DB_CONNECTION'), 'table' => 'job_batches'],
    'failed' => ['driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'), 'database' => env('DB_CONNECTION'), 'table' => 'failed_jobs'],
];
