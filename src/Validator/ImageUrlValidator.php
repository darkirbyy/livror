<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageUrlValidator extends ConstraintValidator
{
    public function __construct(private int $requestTimeout, private HttpClientInterface $httpClient)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (empty($value)) {
            return;
        }

        // Check valid extensions
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $cleanUrl = strtok($value, '?');
        $extension = strtolower(pathinfo($cleanUrl, PATHINFO_EXTENSION));

        if (!in_array($extension, $validExtensions, true)) {
            $this->context->buildViolation($constraint->invalidExtensionMessage)->addViolation();

            return;
        }

        // Check reachability
        try {
            $response = $this->httpClient->request('HEAD', $value, [
                'timeout' => $this->requestTimeout,
            ]);

            $statusCode = $response->getStatusCode();
            $redirectCount = $response->getInfo('redirect_count');
            $contentType = $response->getHeaders()['content-type'][0] ?? null;

            if (200 !== $statusCode || $redirectCount > 0 || !str_starts_with($contentType, 'image/')) {
                $this->context->buildViolation($constraint->notImageMessage)->addViolation();
            }
        } catch (\Exception $e) {
            $this->context->buildViolation($constraint->notReachableMessage)->addViolation();
        }
    }
}
