<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Main\Game;
use App\Enum\SteamSearchStatusEnum as Status;
use App\Service\ExceptionManager;
use App\Service\SteamSearchHelper;
use App\Tests\Mock\DataMock;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SteamSearchHelperTest extends TestCase
{
    private $requestTimeout;
    private $locale;
    private $currency;
    private $exceptionManager;
    private $httpClient;

    private $steamSearchHelper;

    public function setUp(): void
    {
        $this->requestTimeout = 5;
        $this->locale = 'fr_FR';
        $this->currency = 'EUR';
        $this->exceptionManager = $this->createMock(ExceptionManager::class);
        $this->httpClient = new MockHttpClient();

        $this->steamSearchHelper = new SteamSearchHelper($this->requestTimeout, $this->locale, $this->currency, $this->exceptionManager, $this->httpClient);
    }

    #[PU\Test]
    #[PU\DataProvider('fetchGameValues')]
    public function fetchGame(array $body, int $code, Status $expectedStatus, bool $expectedException): void
    {
        $this->httpClient->setResponseFactory(new MockResponse(json_encode($body), ['http_code' => $code, 'error' => $expectedException ? 'exception' : null]));
        $this->exceptionManager
            ->expects($expectedException ? $this->once() : $this->never())
            ->method('handle')
            ->with('warning', $this->stringContains('API'));

        $data = $this->steamSearchHelper->fetchSteamGame(1);

        $this->assertSame($expectedStatus, $this->steamSearchHelper->getStatus());
        $this->assertSame(Status::OK == $expectedStatus ? $body[1]['data'] : null, $data);
    }

    #[PU\Test]
    #[PU\DataProvider('fillGameValues')]
    public function fillGame(int $id, array $data, int $expectedWarning): void
    {
        $game = $this->createMock(Game::class);
        $game->expects($this->once())->method('setSteamId');
        $game->expects($this->once())->method('setName');
        $game->expects($this->once())->method('setTypeGame');
        $game->expects($this->once())->method('setReleaseYear');
        $game->expects($this->once())->method('setFullPrice');
        $game->expects($this->once())->method('setDevelopers');
        $game->expects($this->once())->method('setGenres');
        $game->expects($this->once())->method('setDescription');
        $game->expects($this->once())->method('setImgUrl');

        $this->exceptionManager->expects($this->exactly($expectedWarning))->method('handle')->with('warning', $this->stringContains('parse'));

        $this->steamSearchHelper->fillGame($game, $id, $data);

        // $this->assertSame($expectedStatus, $this->steamSearchHelper->getStatus());
    }

    public static function fetchGameValues(): array
    {
        return [
            'not 200' => [[], 503, Status::ERROR, false],
            'not 200 bis' => [[], 302, Status::ERROR, false],
            'not found' => [[5 => ['success' => true]], 200, Status::NOT_FOUND, false],
            'not found bis' => [[1 => ['success' => false]], 200, Status::NOT_FOUND, false],
            'exception' => [[1 => []], 200, Status::ERROR, true],
            'ok' => [[1 => ['success' => true, 'data' => ['value']]], 200, Status::OK, false],
        ];
    }

    public static function fillGameValues(): array
    {
        return [
            'id 1' => [1, DataMock::$appDetails[1]['data'], 2],
            'id 2' => [2, DataMock::$appDetails[2]['data'], 0],
            'id 3' => [3, DataMock::$appDetails[3]['data'], 0],
        ];
    }
}
