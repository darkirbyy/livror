<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Enum\SteamSearchStatusEnum;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SteamSearchService
{
    private SteamSearchStatusEnum $status = SteamSearchStatusEnum::PENDING;
    private ?int $id = null;
    private array $data = [];

    public function __construct(private HttpClientInterface $client, public int $steamSearchTimeout)
    {
    }

    public function fetchSteamGame(int $id): void
    {
        try {
            $response = $this->client->request('GET', 'https://store.steampowered.com/api/appdetails?appids=' . $id, [
                'max_duration' => $this->steamSearchTimeout,
                'headers' => [
                    'Accept-Language' => 'fr-FR,en-US;q=0.7,en;q=0.3',
                ],
            ]);

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
            $this->id = $id;
            $this->data = $content[$id]['data'];
        } catch (\Exception $e) {
            $this->status = SteamSearchStatusEnum::ERROR;
        }
    }

    public function createNewGame(): Game
    {
        $game = new Game();
        $game->setSteamId($this->id);
        $game->setName($this->data['name'] ?? '');

        // managing release date
        if (!$this->data['release_date']['coming_soon']) {
            $findMonth = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'aout', 'sep.', 'oct.', 'nov.', 'déc.'];
            $replaceMonth = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $dateParsed = str_replace($findMonth, $replaceMonth, $this->data['release_date']['date']);
            $game->setReleaseDate(new \DateTime($dateParsed));
        }

        // managing price
        if ($this->data['is_free']) {
            $game->setFullPrice(0);
        } elseif (isset($this->data['price_overview'])) {
            $game->setFullPrice($this->data['price_overview']['initial'] ?? null);
        } else {
            $game->setFullPrice(null);
        }

        $game->setDevelopers(implode(', ', array_values($this->data['developers'])));
        $game->setGenres(implode(', ', array_column($this->data['genres'], 'description')));
        $game->setDescription($this->data['short_description'] ?? null);
        $game->setImgUrl($this->data['header_image'] ?? null);

        return $game;
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
