<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiMock extends MockHttpClient
{
    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);

        parent::__construct($callback);
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
                throw new \UnexpectedValueException("Missing last_appid and/or if_modified_since parameter in URL: $url");
            }

            return $this->getAppsListMock($query['last_appid'], $query['if_modified_since'] ?? null);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method/$url");
    }

    private function getAppDetailsMock(string $appId): mixed
    {
        $appId = intval($appId);
        $data = DataMock::$appDetails;
        $body = array_key_exists($appId, $data) ? $data[$appId] : ['success' => false];

        return $this->generateMockResponse([$appId => $body]);
    }

    private function getAppsListMock(string $lastAppid, ?string $ifModifiedSince): mixed
    {
        if (is_null($ifModifiedSince)) {
            $apps = array_filter(DataMock::$appsListTruncate, fn (array $app) => $app['appid'] > intval($lastAppid));
        } else {
            $apps = array_filter(DataMock::$appsListUpdate, fn (array $app) => $app['appid'] > intval($lastAppid) && $app['last_modified'] > intval($ifModifiedSince));
        }

        $appsCount = count($apps);
        $apps = array_slice($apps, 0, 5);

        $body = [];
        if (!empty($apps)) {
            $body['apps'] = $apps;
            if ($appsCount > 5) {
                $body['have_more_results'] = true;
                $body['last_appid'] = $apps[4]['appid'];
            }
        }

        return $this->generateMockResponse(['response' => $body]);
    }

    private function generateMockResponse(mixed $body): MockResponse
    {
        return new MockResponse(json_encode($body, JSON_THROW_ON_ERROR), [
            'http_code' => Response::HTTP_OK,
            'response_headers' => [
                'content-type' => 'application/json',
            ],
        ]);
    }
}
