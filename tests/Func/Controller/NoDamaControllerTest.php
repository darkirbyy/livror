<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Entity\Main\Steam;
use App\Fixtures\Story\Game\GameAutocompleteStory;
use App\Fixtures\Story\Steam\SteamAutocompleteStory;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes as PU;

class NoDamaControllerTest extends AbstractControllerTest
{
    public static function setUpBeforeClass(): void
    {
        // Disable DAMA bundle
        parent::setUpBeforeClass();
        StaticDriver::setKeepStaticConnections(false);
    }

    public static function tearDownAfterClass(): void
    {
        // Manually truncate table before enabling DAMA
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ([Steam::class, Game::class, Review::class] as $entityClass) {
            $connection->executeStatement('TRUNCATE TABLE ' . $entityManager->getClassMetadata($entityClass)->getTableName());
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        // Enable DAMA bundle
        StaticDriver::setKeepStaticConnections(true);
        parent::tearDownAfterClass();
    }

    #[PU\Test]
    #[PU\DataProvider('autocompleteSteamValues')]
    public function autocompleteSteam(string $queryString, int $expectedNbSteams): void
    {
        SteamAutocompleteStory::load();

        $this->client->request('GET', '/steam/autocomplete?mode=PATTERN&' . $queryString);

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $contentArray = json_decode($content, true);

        $this->assertResponseIsSuccessful();
        $this->assertJson($content);
        $this->assertArrayHasKey('results', $contentArray);
        $this->assertSame($expectedNbSteams, count($contentArray['results']));
    }

    #[PU\Test]
    #[PU\DataProvider('autocompleteGameValues')]
    public function autocompleteGame(string $queryString, int $expectedNbGames): void
    {
        GameAutocompleteStory::load();

        $this->client->request('GET', '/game/autocomplete?mode=PATTERN&' . $queryString);

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $contentArray = json_decode($content, true);

        $this->assertResponseIsSuccessful();
        $this->assertJson($content);
        $this->assertArrayHasKey('results', $contentArray);
        $this->assertSame($expectedNbGames, count($contentArray['results']));
    }

    public static function autocompleteSteamValues(): array
    {
        return [
            'valid, 2 results' => ['query=goodbye', 2],
            'valid, 4 results' => ['query=good', 4],
        ];
    }

    public static function autocompleteGameValues(): array
    {
        return [
            'valid, with review, 2 results' => ['query=goodbye', 4],
            'valid, without review, max results' => ['withoutReview=true&query=welcome', static::getContainer()->getParameter('app.autocompletion_limit')],
        ];
    }
}
