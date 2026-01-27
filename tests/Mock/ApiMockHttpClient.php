<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiMockHttpClient extends MockHttpClient
{
    public function __construct(private string $discordDir, private Filesystem $filesystem)
    {
        parent::__construct(\Closure::fromCallable([$this, 'handleRequests']));
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        $queryString = parse_url($url, PHP_URL_QUERY) ?? '';
        parse_str($queryString, $query);

        if ('GET' === $method && str_starts_with($url, 'https://api.steampowered.com/ISteamApps/GetAppList/v2')) {
            return $this->getAppsListV1Mock();
        } elseif ('GET' === $method && str_starts_with($url, 'https://api.steampowered.com/IStoreService/GetAppList/v1')) {
            return $this->getAppsListV2Mock($query['last_appid'] ?? null, $query['if_modified_since'] ?? null);
        } elseif ('GET' === $method && str_starts_with($url, 'https://store.steampowered.com/api/appdetails')) {
            return $this->getAppDetailsMock($query['appids'] ?? null);
        } elseif ('HEAD' === $method && str_starts_with($url, 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps')) {
            return $this->getAppImage();
        } elseif ('POST' === $method && str_starts_with($url, 'https://discord.com/api/webhooks')) {
            return $this->getDiscordWebhook($options['body'] ?? null);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method/$url");
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

    private function getAppsListV1Mock(): mixed
    {
        return $this->generateMockResponse(['applist' => ['apps' => ApiMockData::$appsListTruncate]]);
    }

    private function getAppsListV2Mock(?string $lastAppid, ?string $ifModifiedSince): mixed
    {
        if (is_null($lastAppid)) {
            throw new \UnexpectedValueException('Missing last_appid and/or if_modified_since parameter in URL.');
        }

        if (is_null($ifModifiedSince)) {
            $apps = array_filter(ApiMockData::$appsListTruncate, fn(array $app) => $app['appid'] > intval($lastAppid));
        } else {
            $apps = array_filter(ApiMockData::$appsListUpdate, fn(array $app) => $app['appid'] > intval($lastAppid) && $app['last_modified'] >= intval($ifModifiedSince));
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

    private function getAppDetailsMock(?string $appId): mixed
    {
        if (is_null($appId)) {
            throw new \UnexpectedValueException('Missing appids parameter in URL.');
        }

        $appId = intval($appId);
        $data = ApiMockData::$appDetails;
        $body = array_key_exists($appId, $data) ? $data[$appId] : ['success' => false];

        return $this->generateMockResponse([$appId => $body]);
    }

    private function getAppImage(): mixed
    {
        return new MockResponse('', [
            'http_code' => Response::HTTP_OK,
            'response_headers' => [
                'content-type' => 'image/jpg',
            ],
        ]);
    }

    private function getDiscordWebhook(?string $body): mixed
    {
        // Remove the old file
        $this->filesystem->mkdir($this->discordDir);
        $this->filesystem->remove($this->discordDir . '/notify.md');
        $this->filesystem->touch($this->discordDir . '/notify.md');

        // Decode the body, prepre the response code, and write the body is valid
        $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        if (isset($data['content'])) {
            $code = Response::HTTP_OK;
            $this->filesystem->appendToFile($this->discordDir . '/notify.md', $data['content']);
        } else {
            $code = Response::HTTP_BAD_REQUEST;
        }

        // Return the mock response
        return new MockResponse('', [
            'http_code' => $code,
            'response_headers' => [
                'content-type' => 'application/json',
            ],
        ]);
    }
}
