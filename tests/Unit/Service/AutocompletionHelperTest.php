<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Game;
use App\Entity\Steam;
use App\Service\AutocompletionHelper;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Environment;

#[PU\AllowMockObjectsWithoutExpectations]
final class AutocompletionHelperTest extends TestCase
{
    private static $autocompletionMinLength = 5;
    private TranslatorInterface  $trans;
    private StimulusHelper $stimulusHelper;
    private UrlGeneratorInterface $urlGenerator;
    private Environment $twig;

    private AutocompletionHelper $autocompletionHelper;

    public function setUp(): void
    {
        $this->trans = $this->createMock(TranslatorInterface::class);
        $this->stimulusHelper = new StimulusHelper(null);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->autocompletionHelper = new AutocompletionHelper(self::$autocompletionMinLength, $this->trans, $this->stimulusHelper, $this->urlGenerator, $this->twig);
    }

    #[PU\Test]
    #[PU\DataProvider('prepareAttributesValues')]
    public function prepareAttributes(string $placeholderKey, string $route, ?array $params): void
    {
        $this->urlGenerator->expects($this->once())->method('generate')->with($route, $params);
        $this->trans->expects($this->any())->method('trans');

        $stimulusAttributes = $this->autocompletionHelper->prepareAttributes($placeholderKey, $route, $params);

        $this->assertSame(StimulusAttributes::class, $stimulusAttributes::class);
        $this->assertArrayHasKey('data-controller', $stimulusAttributes->toArray());
    }

    #[PU\Test]
    #[PU\DataProvider('renderItemsValues')]
    public function renderItems(int $nbObjects, ?string $objectClass): void
    {
        $objects = [];
        for ($index = 0; $index < $nbObjects; ++$index) {
            $object = $this->createMock($objectClass);
            $object->expects($this->once())->method('getId')->willReturn($index);
            $objects[] = $object;
        }

        $this->twig->expects($this->exactly($nbObjects))->method('render')->willReturn('rendered');

        $data = $this->autocompletionHelper->renderItems('template.html.twig', $objects);

        $this->assertArrayHasKey('results', $data);
        $this->assertSame($nbObjects, count($data['results']));
        array_walk($data['results'], function ($result, $index) {
            $this->assertArrayHasKey('value', $result);
            $this->assertArrayHasKey('text', $result);
            $this->assertEquals($index, $result['value']);
            $this->assertEquals('rendered', $result['text']);
        });
    }

    public static function prepareAttributesValues(): array
    {
        return [
            'without params' => ['steam', 'home_index', []],
            'with params' => ['global', 'game_edit', ['id' => 2]],
        ];
    }

    public static function renderItemsValues(): array
    {
        return [
            'no object' => [0, null],
            '2 objects, game' => [2, Game::class],
            '5 objects, steam' => [5, Steam::class],
        ];
    }
}
