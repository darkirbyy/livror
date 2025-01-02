<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TypePriceEnum: string implements TranslatableInterface
{
    case UNKNOW = 'unknow';
    case FREE = 'free';
    case PAYING = 'paying';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::UNKNOW => $translator->trans('enum.TypePrice.unknown', locale: $locale),
            self::FREE => $translator->trans('enum.TypePrice.free', locale: $locale),
            self::PAYING => $translator->trans('enum.TypePrice.paying', locale: $locale),
        };
    }

    public static function fromPrice(?int $price): TypePriceEnum
    {
        return match (true) {
            $price > 0 => self::PAYING,
            0 === $price => self::FREE,
            default => self::UNKNOW,
        };
    }

    public static function toPrice(TypePriceEnum $typePrice, ?int $price): ?int
    {
        return match ($typePrice) {
            self::UNKNOW => null,
            self::FREE => 0,
            self::PAYING => $price,
        };
    }
}
