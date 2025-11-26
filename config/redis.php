<?php
// config/redis.php

require_once __DIR__ . '/../vendor/autoload.php';

use Predis\Client;

function redis_client(): Client
{
    static $client = null;

    if ($client === null) {
        $redisHost = getenv('REDIS_HOST') ?: 'redis';
        $redisPort = getenv('REDIS_PORT') ?: 6379;

        $client = new Client([
            'scheme' => 'tcp',
            'host'   => $redisHost,
            'port'   => $redisPort,
        ]);
    }

    return $client;
}
