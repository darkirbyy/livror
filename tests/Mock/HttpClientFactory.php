<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HttpClientFactory
{
    public static function create(string $enable, string $discordDir, Filesystem $filesystem): HttpClientInterface
    {
        return $enable ? new ApiMockHttpClient($discordDir, $filesystem) : HttpClient::create();
    }
}
