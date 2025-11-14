<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TypePriceEnum: string implements TranslatableInterface
{
    case UNKNOWN = 'UNKNOWN';
    case FREE = 'FREE';
    case PAYING = 'PAYING';

    // Implement the TranslatableInterface so that the label are automatically translated in the form
    public function trans(TranslatorInterface $trans, ?string $locale = null): string
    {
        return $trans->trans('enum.typePrice.' . $this->toTransKey(), locale: $locale);
    }

    // Convert to key used in the translations yaml
    public function toTransKey(): string
    {
        return match ($this) {
            self::UNKNOWN => 'unknown',
            self::FREE => 'free',
            self::PAYING => 'paying',
        };
    }

    // Determine the type of price from a price (in cent, no decimal)
    public static function fromPrice(?int $price): TypePriceEnum
    {
        return match (true) {
            $price > 0 => self::PAYING,
            0 === $price => self::FREE,
            default => self::UNKNOWN,
        };
    }

    // Determine the value of the price that must be stored in the database, depending on the type of price
    public static function toPrice(TypePriceEnum $typePrice, ?int $price): ?int
    {
        return match ($typePrice) {
            self::UNKNOWN => null,
            self::FREE => 0,
            self::PAYING => $price,
        };
    }
}
