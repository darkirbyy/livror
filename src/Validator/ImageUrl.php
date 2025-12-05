<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ImageUrl extends Constraint
{
    public string $invalidExtensionMessage = 'game.error.imgUrl.notValidExtension';
    public string $notReachableMessage = 'game.error.imgUrl.notReachable';
    public string $notImageMessage = 'game.error.imgUrl.notAnImage';
}
