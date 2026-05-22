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

// Remove final keyword from class to mock
$clientsServicePath = realpath(__DIR__ . '/../vendor/mainick/keycloak-client-bundle/src/Service/ClientsService.php');
DG\BypassFinals::enable(false, true);
DG\BypassFinals::allowPaths([$clientsServicePath]);
