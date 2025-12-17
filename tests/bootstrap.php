<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

// Clear the cache if debug is set to false
if (true === (bool) $_SERVER['APP_DEBUG']) {
    umask(0000);
} else {
    (new Symfony\Component\Filesystem\Filesystem())->remove(__DIR__ . '/../var/cache/test');
}

// Create the test database with the schema
passthru(sprintf('php "%s/../bin/console" --env=test --silent doctrine:database:create', __DIR__));
passthru(sprintf('php "%s/../bin/console" --env=test --silent doctrine:schema:create', __DIR__));
