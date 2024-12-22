<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamSearchService
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    public function fetchSteamGame(int $id)
    {
        $response = $this->client->request('GET', 'https://store.steampowered.com/api/appdetails?appids=' . $id, [
            'timeout' => 2,
        ]);

        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $content = $response->toArray();

        return $content;
    }
}
