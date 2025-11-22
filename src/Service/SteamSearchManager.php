<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Main\Game;
use App\Enum\SteamSearchStatusEnum;
use App\Enum\TypeGameEnum;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamSearchManager
{
    private SteamSearchStatusEnum $status = SteamSearchStatusEnum::PENDING;
    private ?int $id = null;
    private array $data = [];

    public function __construct(
        private ExceptionManager $exceptionManager,
        private HttpClientInterface $client,
        private int $timeout,
        private string $locale,
        private string $currency,
    ) {
    }

    public function fetchSteamGame(int $id): void
    {
        try {
            // Make the call to the external steam API, in the locale of the application
            $response = $this->client->request('GET', 'https://store.steampowered.com/api/appdetails?appids=' . $id, [
                'max_duration' => $this->timeout,
                'headers' => [
                    'Accept-Language' => str_replace('_', '-', $this->locale) . ';q=0.5',
                ],
            ]);

            // Error is the status code is not 200
            if (200 !== $response->getStatusCode()) {
                $this->status = SteamSearchStatusEnum::ERROR;

                return;
            }

            // Not found is the response does not contain the requested id, or is not labeled "success"
            $content = $response->toArray();
            if (!isset($content[$id]) || !$content[$id]['success']) {
                $this->status = SteamSearchStatusEnum::NOT_FOUND;

                return;
            }

            // Store the data of the game for later user
            $this->status = SteamSearchStatusEnum::OK;
            $this->id = $id;
            $this->data = $content[$id]['data'];
        } catch (\Exception $e) {
            // Catch any other kind of errors
            $this->exceptionManager->handle('warning', 'Error while making steam API call with steamId: {steamId}. Error: {error}', [
                'steamId' => $this->id,
                'error' => $e->getMessage(),
            ]);
            $this->status = SteamSearchStatusEnum::ERROR;
        }
    }

    public function fillGame(Game $game): Game
    {
        $game->setSteamId($this->id);

        // Managing name : empty string is not present in the response
        $game->setName($this->data['name'] ?? '');

        // Managing type of game : use other as defautlt value
        $game->setTypeGame(TypeGameEnum::fromSteam($this->data['type'] ?? ''));

        // Managing release year : use a regex to find 4 consecutive digits in the response, null otherwise
        $releaseDate = $this->data['release_date']['date'] ?? null;
        if ($releaseDate && preg_match('/(\d{4})/', $releaseDate, $matches)) {
            $game->setReleaseYear((int) $matches[1]);
        } else {
            $game->setReleaseYear(null);
            $this->exceptionManager->handle('warning', 'Unable to parse the release year or release date not found for the game with steamId: {steamId}', ['steamId' => $this->id]);
        }

        // Managing price : 0 = free, otherwise = check if the currency is the same as the application and the price is a valid integer, else null
        if (!empty($this->data['is_free'])) {
            $game->setFullPrice(0);
        } else {
            $currency = $this->data['price_overview']['currency'] ?? null;
            $fullPrice = $this->data['price_overview']['initial'] ?? null;

            if ($currency === $this->currency && is_numeric($fullPrice) && $fullPrice > 0) {
                $game->setFullPrice((int) $fullPrice);
            } else {
                $game->setFullPrice(null);
                $this->exceptionManager->handle('warning', 'Unable to parse the price or invalid currency for the game with steamId: {steamId}', ['steamId' => $this->id]);
            }
        }

        // Managing developers : regroup all of them with commas, null if not present in the response
        $game->setDevelopers(!empty($this->data['developers']) ? implode(', ', $this->data['developers']) : null);

        // Managing genres : regroup all of them with commas, null if not present in the response (be careful to extract the description column)
        $game->setGenres(!empty($this->data['genres']) ? implode(', ', array_column($this->data['genres'], 'description')) : null);

        // Managing description : null if not present in the response
        $game->setDescription($this->data['short_description'] ?? null);

        // Managing image URL : null if not present in the response
        $game->setImgUrl($this->data['header_image'] ?? null);

        return $game;
    }

    public function getStatus(): SteamSearchStatusEnum
    {
        return $this->status;
    }
}
