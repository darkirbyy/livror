<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Entity\Account\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AccountEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private AuthenticationEntryPointInterface $inner,
        private ParameterBagInterface $parameterBag,
        private Security $security,
        private EntityManagerInterface $accountEntityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        if ($this->parameterBag->get('app.mock_hub')) {
            $user = $this->accountEntityManager->getRepository(User::class)->findOneBy(['username' => 'user1']);
            $this->security->login($user);

            return new RedirectResponse($this->urlGenerator->generate('home_index'));
        } else {
            return $this->inner->start($request, $authException);
        }
    }
}
