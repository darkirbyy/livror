<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HttpClientFactory
{
    public static function create(string $enable): HttpClientInterface
    {
        return $enable ? new ApiMockHttpClient() : HttpClient::create();
    }
}
