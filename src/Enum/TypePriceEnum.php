<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TypePriceEnum: string implements TranslatableInterface
{
    case UNKNOWN = 'unknown';
    case FREE = 'free';
    case PAYING = 'paying';

    // Implement the TranslatableInterface so that the label are automatically translated in the form
    public function trans(TranslatorInterface $trans, ?string $locale = null): string
    {
        return match ($this) {
            self::UNKNOWN => $trans->trans('enum.TypePrice.unknown', locale: $locale),
            self::FREE => $trans->trans('enum.TypePrice.free', locale: $locale),
            self::PAYING => $trans->trans('enum.TypePrice.paying', locale: $locale),
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
