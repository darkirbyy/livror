<?php

declare(strict_types=1);

namespace App\Extension;

use App\Service\UserManager;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TwigGlobal extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private UserManager $userManager,
    ) {}

    public function getGlobals(): array
    {
        return [
            'userConnected' => $this->userManager->getUserConnected()
        ];
    }
}
