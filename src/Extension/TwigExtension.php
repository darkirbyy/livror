<?php

declare(strict_types=1);

namespace App\Extension;

use App\Enum\TypePriceEnum;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private TranslatorInterface $trans)
    {
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFilters(): array
    {
        return [new TwigFilter('fmt_full_price', [$this, 'formatFullPrice'])];
    }

    public function formatFullPrice(?int $fullPrice, string $locale): string
    {
        $typePrice = TypePriceEnum::fromPrice($fullPrice);

        return TypePriceEnum::UNKNOWN != $typePrice ? $typePrice->trans($this->trans, $locale) : '';
    }
}
