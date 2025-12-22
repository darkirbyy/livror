<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Account\User;
use App\Service\HubUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AccountEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private bool $mockHub,
        private Security $security,
        private EntityManagerInterface $accountEntityManager,
        private UrlGeneratorInterface $urlGenerator,
        private HubUrlGenerator $hubUrlGenerator,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        if ($this->mockHub) {
            $user = $this->accountEntityManager->getRepository(User::class)->findOneBy(['username' => 'user1']);
            $this->security->login($user);

            return new RedirectResponse($this->urlGenerator->generate('home_index'));
        } else {
            $request->getSession()->set('hub/login-target-path', $request->getUri());

            return new RedirectResponse($this->hubUrlGenerator->generateAccount('/login'));
        }
    }
}
