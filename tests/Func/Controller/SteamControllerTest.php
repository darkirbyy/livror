<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Fixtures\Story\Steam\SteamAutocompleteStory;
use PHPUnit\Framework\Attributes as PU;

class SteamControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    #[PU\DataProvider('autocompleteValues')]
    public function autocomplete(string $queryString, int $expectedNbGames): void
    {
        SteamAutocompleteStory::load();

        $this->client->request('GET', '/steam/autocomplete?' . $queryString);

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $contentArray = json_decode($content, true);

        $this->assertResponseIsSuccessful();
        $this->assertJson($content);
        $this->assertArrayHasKey('results', $contentArray);
        $this->assertSame($expectedNbGames, count($contentArray['results']));
    }

    public static function autocompleteValues(): array
    {
        return [
            'invalid, wrong parameter' => ['search=welcom', 0],
            'invalid, too short' => ['query=we', 0],
            'valid, max results' => ['query=welcome', static::getContainer()->getParameter('app.autocompletion_limit')],
            'valid 2 results' => ['query=goodbye', 2],
            'valid 4 results' => ['query=good', 4],
        ];
    }
}
