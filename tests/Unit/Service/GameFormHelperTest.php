<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Main\Game;
use App\Enum\SteamSearchStatusEnum as Status;
use App\Service\GameFormHelper;
use App\Service\SteamSearchHelper;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GameFormHelperTest extends TestCase
{
    private $form;
    private $game;
    private $session;

    private $requestStack;
    private $trans;
    private $steamSearchHelper;

    private $gameFormHelper;

    public function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->form = $this->createMock(FormInterface::class);
        $this->game = $this->createMock(Game::class);

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->trans = $this->createMock(TranslatorInterface::class);
        $this->steamSearchHelper = $this->createMock(SteamSearchHelper::class);

        $this->gameFormHelper = new GameFormHelper($this->requestStack, $this->trans, $this->steamSearchHelper);
    }

    #[PU\Test]
    public function processNull(): void
    {
        $this->steamSearchHelper->expects($this->never())->method('fetchSteamGame');
        $this->gameFormHelper->process($this->game, $this->form, null);
    }

    #[PU\Test]
    #[PU\DataProvider('processNoSearchValues')]
    public function processNoSearch(string $steamId, string $transKey): void
    {
        $expectedMessage = 'game.error.steamId.' . $transKey;

        $this->steamSearchHelper->expects($this->never())->method('fetchSteamGame');
        $this->form->expects($this->once())->method('get')->with('steamId');
        $this->trans->expects($this->once())->method('trans')->with($expectedMessage);

        $this->gameFormHelper->process($this->game, $this->form, $steamId);
    }

    #[PU\Test]
    #[PU\DataProvider('processSearchValues')]
    public function processSearchOk(string $steamId): void
    {
        $expectedMessage = 'game.edit.flash.steamSearch.success';
        $data = [];

        $this->steamSearchHelper
            ->expects($this->once())
            ->method('fetchSteamGame')
            ->with($steamId)
            ->willReturn([Status::OK, $data]);
        $this->steamSearchHelper->expects($this->once())->method('fillGame')->with($this->game, $steamId, $data);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);

        $this->gameFormHelper->process($this->game, $this->form, $steamId);
        $this->assertSame($expectedMessage, $this->session->getFlashBag()->get('livror/success')[0]->message);
    }

    #[PU\Test]
    #[PU\DataProvider('processSearchValues')]
    public function processSearchNotFound(string $steamId): void
    {
        $expectedMessage = 'game.error.steamId.notFound';

        $this->steamSearchHelper
            ->expects($this->once())
            ->method('fetchSteamGame')
            ->with($steamId)
            ->willReturn([Status::NOT_FOUND, []]);
        $this->form->expects($this->once())->method('get')->with('steamId');
        $this->trans->expects($this->once())->method('trans')->with($expectedMessage);

        $this->gameFormHelper->process($this->game, $this->form, $steamId);
    }

    #[PU\Test]
    #[PU\DataProvider('processSearchValues')]
    public function processSearchFailed(string $steamId): void
    {
        $expectedMessage = 'game.edit.flash.steamSearch.fail';

        $this->steamSearchHelper
            ->expects($this->once())
            ->method('fetchSteamGame')
            ->with($steamId)
            ->willReturn([Status::ERROR, []]);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);

        $this->gameFormHelper->process($this->game, $this->form, $steamId);
        $this->assertSame($expectedMessage, $this->session->getFlashBag()->get('livror/danger')[0]->message);
    }

    public static function processNoSearchValues(): array
    {
        return [
            'empty' => ['', 'empty'],
            'digits and letters' => ['95abc', 'invalid'],
            'number as words' => ['one', 'invalid'],
            'negative number' => ['-264', 'invalid'],
        ];
    }

    public static function processSearchValues(): array
    {
        return [
            'valid number' => ['10'],
        ];
    }
}
