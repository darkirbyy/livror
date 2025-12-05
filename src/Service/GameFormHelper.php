<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FlashMessage;
use App\Entity\Main\Game;
use App\Enum\SteamSearchStatusEnum;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class GameFormHelper
{
    public function __construct(private RequestStack $requestStack, private TranslatorInterface $trans, private SteamSearchManager $steamSearchManager)
    {
    }

    public function process(Game $game, FormInterface $form, ?string $steamId): void
    {
        if (null === $steamId) {
            return;
        }

        if ('' === $steamId) {
            $this->addFormError($form, 'empty');

            return;
        }

        if (!ctype_digit($steamId)) {
            $this->addFormError($form, 'invalid');

            return;
        }

        $this->steamSearchManager->fetchSteamGame((int) $steamId);

        switch ($this->steamSearchManager->getStatus()) {
            case SteamSearchStatusEnum::OK:
                $this->steamSearchManager->fillGame($game);
                $this->addFlashMessage('success', 'success');
                break;

            case SteamSearchStatusEnum::NOT_FOUND:
                $this->addFormError($form, 'notFound');
                break;

            default:
                $this->addFlashMessage('danger', 'fail');
        }
    }

    private function addFormError(FormInterface $form, string $transKey): void
    {
        $form->get('steamId')->addError(new FormError($this->trans->trans('game.error.steamId.' . $transKey, [], 'validators')));
    }

    private function addFlashMessage(string $type, string $transKey): void
    {
        $flashBag = $this->requestStack->getSession()->getFlashBag();
        $flashBag->add('livror/' . $type, new FlashMessage('game.edit.flash.steamSearch.' . $transKey));
    }
}
