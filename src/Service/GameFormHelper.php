<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\FlashMessage;
use App\Entity\Game;
use App\Enum\SteamSearchStatusEnum;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class GameFormHelper
{
    public function __construct(private RequestStack $requestStack, private TranslatorInterface $trans, private SteamSearchHelper $steamSearchHelper) {}

    /**
     * Allow to pre-fill a form (with data class Gam) by filling a Game entity using a steamId
     * and by adding error/flash depending on the given values and request status.
     */
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

        $id = (int) $steamId;
        [$status, $data] = $this->steamSearchHelper->fetchSteamGame($id);

        switch ($status) {
            case SteamSearchStatusEnum::OK:
                $this->steamSearchHelper->fillGame($game, $id, $data);
                $this->addFlashMessage('success', 'success');
                break;

            case SteamSearchStatusEnum::NOT_FOUND:
                $this->addFormError($form, 'notFound');
                break;

            default:
                $this->addFlashMessage('danger', 'fail');
        }
    }

    /**
     * Add an error to the form, using the given translation key.
     */
    private function addFormError(FormInterface $form, string $transKey): void
    {
        $form->get('steamId')->addError(new FormError($this->trans->trans('game.error.steamId.' . $transKey, [], 'validators')));
    }

    /**
     * Add flash message of given type to the session, using the given translation key.
     */
    private function addFlashMessage(string $type, string $transKey): void
    {
        $flashBag = $this->requestStack->getSession()->getFlashBag();
        $flashBag->add($type, new FlashMessage('game.edit.flash.steamSearch.' . $transKey));
    }
}
