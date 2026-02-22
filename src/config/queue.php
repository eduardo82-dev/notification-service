<?php

return [
    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'      => 'database',
            'connection'  => env('DB_QUEUE_CONNECTION'),
            'table'       => env('DB_QUEUE_TABLE', 'jobs'),
            'queue'       => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'redis' => [
            'driver'      => 'redis',
            'connection'  => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue'       => env('REDIS_QUEUE', '{default}'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for'   => null,
            'after_commit' => false,
        ],

        'rabbitmq' => [
            'driver'  => 'rabbitmq',
            'queue'   => env('RABBITMQ_QUEUE', 'notifications'),
            'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,

            'hosts' => [
                [
                    'host'     => env('RABBITMQ_HOST', 'app01-rabbitmq'),
                    'port'     => env('RABBITMQ_PORT', 5672),
                    'user'     => env('RABBITMQ_USER', 'rabbit_user'),
                    'password' => env('RABBITMQ_PASSWORD', 'rabbit_pass'),
                    'vhost'    => env('RABBITMQ_VHOST', 'notifications'),
                ],
            ],

            'options' => [
                'ssl_options' => [
                    'cafile'      => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert'  => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_key'   => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase'  => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
                'queue' => [
                    'job' => \VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
                ],
            ],

            'worker' => env('RABBITMQ_WORKER', 'default'),

            // Exchange settings
            'exchange'              => env('RABBITMQ_EXCHANGE', 'notifications.direct'),
            'exchange_type'         => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
            'exchange_routing_key'  => env('RABBITMQ_EXCHANGE_ROUTING_KEY', 'notifications'),
        ],
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'job_batches',
    ],

    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],
];
