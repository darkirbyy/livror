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
        $queryString = parse_url($url, PHP_URL_QUERY);
        parse_str($queryString, $query);

        if ('GET' === $method && str_starts_with($url, 'https://store.steampowered.com/api/appdetails')) {
            if (!isset($query['appids'])) {
                throw new \UnexpectedValueException("Missing appids parameter in URL: $url");
            }

            return $this->getAppDetailsMock($query['appids']);
        } elseif ('GET' === $method && str_starts_with($url, 'https://api.steampowered.com/IStoreService/GetAppList/v1')) {
            if (!isset($query['last_appid'])) {
                throw new \UnexpectedValueException("Missing last_appid parameter in URL: $url");
            }

            return $this->getAppsListMock($query['last_appid']);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method/$url");
    }

    private function getAppDetailsMock(string $id): mixed
    {
        $data = DataMock::$appDetails;
        $body = json_encode(array_key_exists($id, $data) ? [$id => $data[$id]] : [$id => ['success' => false]], JSON_THROW_ON_ERROR);

        return $this->generateMockResponse($body);
    }

    private function getAppsListMock(string $id): mixed
    {
        $data = DataMock::$appsList;
        $body = array_key_exists($id, $data) ? $data[$id] : '{"response":{}}';

        return $this->generateMockResponse($body);
    }

    private function generateMockResponse(mixed $body): MockResponse
    {
        return new MockResponse($body, [
            'http_code' => Response::HTTP_OK,
            'response_headers' => [
                'content-type' => 'application/json',
            ],
        ]);
    }
}
