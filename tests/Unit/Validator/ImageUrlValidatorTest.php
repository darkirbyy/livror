<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Validator\ImageUrl;
use App\Validator\ImageUrlValidator;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ImageUrlValidatorTest extends ConstraintValidatorTestCase
{
    private static $requestTimeout = 5;
    private $httpClient;

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->httpClient = new MockHttpClient();

        return new ImageUrlValidator(self::$requestTimeout, $this->httpClient);
    }

    #[PU\Test]
    #[PU\DataProvider('noViolationValues')]
    public function noViolation(?string $imgUrl, int $code, bool $exception, string $contentType): void
    {
        $infos = [
            'http_code' => $code,
            'error' => $exception ? 'exception' : null,
            'response_headers' => ['Content-Type: ' . $contentType],
        ];
        $this->httpClient->setResponseFactory(new MockResponse('', $infos));
        $this->validator->validate($imgUrl, new ImageUrl());
        $this->assertNoViolation();
    }

    #[PU\Test]
    #[PU\DataProvider('violationValues')]
    public function violation(string $imgUrl, int $code, bool $exception, string $contentType, string $expectedMessage): void
    {
        $infos = [
            'http_code' => $code,
            'error' => $exception ? 'exception' : null,
            'response_headers' => ['Content-Type: ' . $contentType],
        ];
        $this->httpClient->setResponseFactory(new MockResponse('', $infos));
        $this->validator->validate($imgUrl, new ImageUrl());
        $this->buildViolation($expectedMessage)->assertRaised();
    }

    public static function noViolationValues(): array
    {
        return [
            'null' => [null, 200, false, 'image/jpg', 'game.error.imgUrl.notValidExtension'],
            'valid' => ['image.jpg', 200, false, 'image/jpg'],
        ];
    }

    public static function violationValues(): array
    {
        return [
            'wrong extension' => ['image.exe', 200, false, 'image/jpg', 'game.error.imgUrl.notValidExtension'],
            'not 200' => ['image.jpg', 404, false, 'image/jpg', 'game.error.imgUrl.notAnImage'],
            // 'redirect' => ['image.jpg', 500, false, 'image/jpg', 'game.error.imgUrl.notAnImage'],
            'wrong content type' => ['image.jpg', 200, false, 'application/json', 'game.error.imgUrl.notAnImage'],
            'error' => ['image.jpg', 200, true, 'image/jpg', 'game.error.imgUrl.notReachable'],
        ];
    }
}
