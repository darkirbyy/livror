<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Enum\SteamSearchStatusEnum;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamSearchService
{
    private SteamSearchStatusEnum $status = SteamSearchStatusEnum::PENDING;
    private ?int $id = null;
    private array $data = [];

    public function __construct(private HttpClientInterface $client, private LoggerInterface $logger, public int $timeout, public string $locale, public string $currency)
    {
    }

    public function fetchSteamGame(int $id): void
    {
        try {
            $response = $this->client->request('GET', 'https://store.steampowered.com/api/appdetails?appids=' . $id, [
                'max_duration' => $this->timeout,
                'headers' => [
                    'Accept-Language' => $this->locale,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if (200 !== $statusCode) {
                $this->status = SteamSearchStatusEnum::ERROR;

                return;
            }

            $content = $response->toArray();

            if (!isset($content[$id]) || !$content[$id]['success']) {
                $this->status = SteamSearchStatusEnum::NOT_FOUND;

                return;
            }

            $this->status = SteamSearchStatusEnum::OK;
            $this->id = $id;
            $this->data = $content[$id]['data'];
        } catch (\Exception $e) {
            $this->status = SteamSearchStatusEnum::ERROR;
        }
    }

    public function fillGame(Game $game): Game
    {
        $game->setSteamId($this->id);
        $game->setName($this->data['name'] ?? '');

        // managing release year
        $releaseDate = $this->data['release_date']['date'] ?? null;
        if ($releaseDate && preg_match('/(\d{4})/', $releaseDate, $matches)) {
            $game->setReleaseYear((int) $matches[1]);
        } else {
            $game->setReleaseYear(null);
            $this->logger->warning('Unable to parse the release year or release date not found for the game with steamId: {steamId}', ['steamId' => $this->id]);
        }

        // managing price
        if (!empty($this->data['is_free'])) {
            $game->setFullPrice(0);
        } else {
            $currency = $this->data['price_overview']['currency'] ?? null;
            $fullPrice = $this->data['price_overview']['initial'] ?? null;

            if ($currency === $this->currency && is_numeric($fullPrice) && $fullPrice > 0) {
                $game->setFullPrice((int) $fullPrice);
            } else {
                $game->setFullPrice(null);
                $this->logger->warning('Unable to parse the price or invalid currency for the game with steamId: {steamId}', ['steamId' => $this->id]);
            }
        }

        $game->setDevelopers(!empty($this->data['developers']) ? implode(', ', $this->data['developers']) : null);
        $game->setGenres(!empty($this->data['genres']) ? implode(', ', array_column($this->data['genres'], 'description')) : null);
        $game->setDescription($this->data['short_description'] ?? null);
        $game->setImgUrl($this->data['header_image'] ?? null);

        return $game;
    }

    public function getStatus(): SteamSearchStatusEnum
    {
        return $this->status;
    }
}
