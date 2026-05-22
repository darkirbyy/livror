<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\UserManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private string $hubUrl, private UserManager $userManager) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $user = $this->userManager->getUserConnected();

        return is_null($user) ? new RedirectResponse($this->hubUrl) : null;
    }
}
