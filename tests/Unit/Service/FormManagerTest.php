<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\FlashMessage;
use App\Service\ExceptionManager;
use App\Service\FormManager;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[PU\AllowMockObjectsWithoutExpectations]
final class FormManagerTest extends TestCase
{
    private DriverException $driverException;
    private Request $request;
    private Session $session;
    private object $game;
    private FormInterface $form;

    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private ExceptionManager $exceptionManager;

    private FormManager $formManager;

    public function setUp(): void
    {
        $fakeDriver = new class extends \Exception implements \Doctrine\DBAL\Driver\Exception {
            public function getSQLState(): ?string
            {
                return null;
            }
        };
        $this->driverException = new DriverException($fakeDriver, null);
        $this->request = new Request(content: '{"_token":"tokenValue"}');
        $this->session = new Session(new MockArraySessionStorage());
        $this->game = new class {};
        $this->form = $this->createMock(FormInterface::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->exceptionManager = $this->createMock(ExceptionManager::class);

        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);

        $this->formManager = new FormManager($this->entityManager, $this->requestStack, $this->csrfTokenManager, $this->exceptionManager);
    }

    #[PU\Test]
    #[PU\DataProvider('validateAndPersistCheckValues')]
    public function validateAndPersistCheck(bool $isSubmitted, bool $isValid): void
    {
        $this->form->expects($this->once())->method('isSubmitted')->willReturn($isSubmitted);
        $this->form->expects($this->any())->method('isValid')->willReturn($isValid);

        $success = $this->formManager->validateAndPersist($this->form, $this->game, null);

        $this->assertSame($isSubmitted && $isValid, $success);
    }

    #[PU\Test]
    public function validateAndPersistInvalidPersist(): void
    {
        $this->form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new ConstraintViolationException($this->driverException, null));
        $this->form->expects($this->once())->method('addError');

        $success = $this->formManager->validateAndPersist($this->form, $this->game, null);

        $this->assertSame(false, $success);
    }

    #[PU\Test]
    public function checkTokenAndRemoveValid(): void
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->csrfTokenManager->expects($this->once())->method('isTokenValid')->willReturn(true);

        $this->assertTrue($this->formManager->checkTokenAndRemove('tokenId', $this->game, null));
    }

    #[PU\Test]
    public function checkTokenAndRemoveInvalid(): void
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->csrfTokenManager->expects($this->once())->method('isTokenValid')->willReturn(false);

        $this->assertFalse($this->formManager->checkTokenAndRemove('tokenId', $this->game, null));
        $this->assertSame('form.flash.invalidCsrf', $this->session->getFlashBag()->get('danger')[0]->message);
    }

    #[PU\Test]
    public function persitValidWithFlash(): void
    {
        $successMessage = 'success';

        $this->entityManager->expects($this->once())->method('persist')->with($this->game);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->formManager->persist($this->game, new FlashMessage($successMessage)));
        $this->assertSame('success', $this->session->getFlashBag()->get('success')[0]->message);
    }

    #[PU\Test]
    public function persitValidWithoutFlash(): void
    {
        $this->entityManager->expects($this->once())->method('persist')->with($this->game);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->formManager->persist($this->game));
    }

    #[PU\Test]
    public function persitInvalid(): void
    {
        $errorMessage = 'error';

        $this->entityManager->expects($this->once())->method('persist')->with($this->game);
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new ConstraintViolationException($this->driverException, null));
        $this->exceptionManager->expects($this->once())->method('handleDatabase')->willReturn($errorMessage);

        $this->assertFalse($this->formManager->persist($this->game));
        $this->assertSame($errorMessage, $this->session->getFlashBag()->get('danger')[0]->message);
    }

    #[PU\Test]
    public function removeValidWithFlash(): void
    {
        $successMessage = 'success';

        $this->entityManager->expects($this->once())->method('remove')->with($this->game);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->formManager->remove($this->game, new FlashMessage($successMessage)));
        $this->assertSame('success', $this->session->getFlashBag()->get('success')[0]->message);
    }

    #[PU\Test]
    public function removeValidWithoutFlash(): void
    {
        $this->entityManager->expects($this->once())->method('remove')->with($this->game);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->formManager->remove($this->game));
    }

    #[PU\Test]
    public function removeInvalid(): void
    {
        $errorMessage = 'error';

        $this->entityManager->expects($this->once())->method('remove')->with($this->game);
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new ConstraintViolationException($this->driverException, null));
        $this->exceptionManager->expects($this->once())->method('handleDatabase')->willReturn($errorMessage);

        $this->assertFalse($this->formManager->remove($this->game));
        $this->assertSame($errorMessage, $this->session->getFlashBag()->get('danger')[0]->message);
    }

    public static function validateAndPersistCheckValues(): array
    {
        return [
            'both true' => [true, true],
            'valid false' => [true, false],
            'submitted false' => [false, true],
            'both false' => [false, false],
        ];
    }
}
