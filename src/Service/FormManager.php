<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Service to persist/remove any entity into/from the database, handling the flash messages and logging the errors.
 */
class FormManager
{
    private FlashBagInterface $flashBag;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private ExceptionManager $exceptionManager,
    ) {
        $this->flashBag = $requestStack->getSession()->getFlashBag();
    }

    /**
     * Validate an entity according to the form and persist it with a custom flash message upon success.
     */
    public function validateAndPersist(FormInterface $form, object $object, ?string $flashSuccess = null): bool
    {
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->persist($object, $flashSuccess);
        }

        return false;
    }

    /**
     * Check the CSRF token and persist the entity with a custom flash message upon success.
     */
    public function checkTokenAndPersist(string $tokenName, object $object, ?string $flashSuccess = null): bool
    {
        $tokenValue = $this->requestStack
            ->getCurrentRequest()
            ->getPayload()
            ->get($tokenName . '_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($tokenName, $tokenValue))) {
            $this->flashBag->add('error', ['message' => 'form.flash.invalidCsrf']);

            return false;
        }

        return $this->persist($object, $flashSuccess);
    }

    /**
     * Check the CSRF token and remove the entity with a custom flash message upon success.
     */
    public function checkTokenAndRemove(string $tokenName, object $object, ?string $flashSuccess = null): bool
    {
        $tokenValue = $this->requestStack
            ->getCurrentRequest()
            ->getPayload()
            ->get($tokenName . '_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($tokenName, $tokenValue))) {
            $this->flashBag->add('error', ['message' => 'form.flash.invalidCsrf']);

            return false;
        }

        return $this->remove($object, $flashSuccess);
    }

    /**
     * Persists an entity into the database, displaying a customizable success flash message upon success.
     * Catches any constraint exception to log it and display a general error flash message otherwise.
     */
    public function persist(object $object, ?array $flashSuccess = null): bool
    {
        try {
            $this->entityManager->persist($object);
            $this->entityManager->flush();

            if (!empty($flashSuccess)) {
                $this->flashBag->add('success', $flashSuccess);
            }

            return true;
        } catch (ConstraintViolationException $e) {
            $message = $this->exceptionManager->handleDatabase($e);
            $this->flashBag->add('danger', ['message' => $message]);

            return false;
        }
    }

    /**
     * Removes an entity from the database, displaying a customizable success flash message upon success.
     * Catches any constraint exception to log it and display a general error flash message otherwise.
     */
    public function remove(object $object, ?array $flashSuccess = null): bool
    {
        try {
            $this->entityManager->remove($object);
            $this->entityManager->flush();

            if (!empty($flashSuccess)) {
                $this->flashBag->add('success', $flashSuccess);
            }

            return true;
        } catch (ConstraintViolationException $e) {
            $message = $this->exceptionManager->handleDatabase($e);
            $this->flashBag->add('danger', ['message' => $message]);

            return false;
        }
    }
}
