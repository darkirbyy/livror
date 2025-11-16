<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiMock extends MockHttpClient
{
    private string $baseUri = 'https://store.steampowered.com/api';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);

        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url): MockResponse
    {
        if ('GET' === $method && str_starts_with($url, $this->baseUri . '/appdetails?appids=')) {
            $query = parse_url($url, PHP_URL_QUERY);
            parse_str($query, $params);

            if (isset($params['appids'])) {
                $id = $params['appids'];

                return $this->getAppDetailsMock($id);
            }

            throw new \UnexpectedValueException("Missing appids parameter in URL: $url");
        }

        throw new \UnexpectedValueException("Mock not implemented: $method/$url");
    }

    private function generateMockResponse(mixed $data): MockResponse
    {
        return new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), [
            'http_code' => Response::HTTP_OK,
            'response_headers' => [
                'content-type' => 'application/json',
            ],
        ]);
    }

    private function getAppDetailsMock(string $id): mixed
    {
        $data = DataMock::$steamApps;

        return $this->generateMockResponse(array_key_exists($id, $data) ? [$id => $data[$id]] : [$id => ['success' => false]]);
    }
}
