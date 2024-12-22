<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Enum\SteamSearchStatusEnum;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamSearchService
{
    private SteamSearchStatusEnum $status = SteamSearchStatusEnum::PENDING;
    private array $data = [];

    public function __construct(private HttpClientInterface $client, public int $steamSearchTimeout)
    {
    }

    public function fetchSteamGame(int $id): void
    {
        try {
            $response = $this->client->request('GET', 'https://store.steampowered.com/api/appdetails?appids=' . $id, ['max_duration' => $this->steamSearchTimeout]);

            $statusCode = $response->getStatusCode();

            if (200 !== $statusCode) {
                $this->status = SteamSearchStatusEnum::ERROR;

                return;
            }

            $content = $response->toArray();

            if (!isset($content[$id]) || !$content[$id]['success']) {
                $this->status = SteamSearchStatusEnum::INVALID_ID;

                return;
            }

            $this->status = SteamSearchStatusEnum::OK;
            $this->data = $content[$id]['data'];
        } catch (\Exception $e) {
            $this->status = SteamSearchStatusEnum::ERROR;
        }
    }

    public function fillGameData(Game $game)
    {
        $game->setName($this->data['name'] ?? '');

        // managing release date
        if (!$this->data['release_date']['coming_soon']) {
            $findMonth = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'aout', 'sep.', 'oct.', 'nov.', 'déc.'];
            $replaceMonth = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $dateParsed = str_replace($findMonth, $replaceMonth, $this->data['release_date']['date']);
            $game->setReleaseDate(new \DateTime($dateParsed));
        }

        // managing price
        if (isset($this->data['price_overview'])) {
            $game->setFullPrice($this->data['price_overview']['initial'] ?? '');
        }

        $game->setDevelopers(implode(', ', array_values($this->data['developers'])));
        $game->setGenres(implode(', ', array_column($this->data['genres'], 'description')));
        $game->setDescription($this->data['short_description'] ?? '');
        $game->setImgUrl($this->data['header_image'] ?? '');
    }

    public function getStatus(): SteamSearchStatusEnum
    {
        return $this->status;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
