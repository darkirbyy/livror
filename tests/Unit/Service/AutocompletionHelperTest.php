<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AutocompletionHelper;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

final class AutocompletionHelperTest extends TestCase
{
    private static $autocompletionMinLength = 5;
    private $trans;
    private $stimulusHelper;
    private $urlGenerator;

    private $autocompletionHelper;

    public function setUp(): void
    {
        $this->trans = $this->createMock(TranslatorInterface::class);
        $this->stimulusHelper = new StimulusHelper(null);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->autocompletionHelper = new AutocompletionHelper(self::$autocompletionMinLength, $this->trans, $this->stimulusHelper, $this->urlGenerator);
    }

    #[PU\Test]
    #[PU\DataProvider('prepareAttributesValues')]
    public function prepareAttributes(string $route, ?array $params): void
    {
        $this->urlGenerator->expects($this->once())->method('generate')->with($route, $params);
        $this->trans->expects($this->any())->method('trans');
        $stimulusAttributes = $this->autocompletionHelper->prepareAttributes($route, $params);
        $this->assertSame(StimulusAttributes::class, $stimulusAttributes::class);
        $this->assertArrayHasKey('data-controller', $stimulusAttributes->toArray());
    }

    public static function prepareAttributesValues(): array
    {
        return [
            'without params' => ['home_index', []],
            'with params' => ['game_edit', ['id' => 2]],
        ];
    }
}
