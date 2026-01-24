<?php

return [
    'adapters' => [
        'http' => \App\Downstream\Adapters\HttpAdapter::class,
        'file' => \App\Downstream\Adapters\FileAdapter::class,
        'sentry' => \App\Downstream\Adapters\SentryAdapter::class,
    ],
];
