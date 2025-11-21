<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TypeGameEnum: string implements TranslatableInterface
{
    case GAME = 'GAME';
    case DLC = 'DLC';
    case DEMO = 'DEMO';
    case OTHER = 'OTHER';

    // Implement the TranslatableInterface so that the label are automatically translated in the form
    public function trans(TranslatorInterface $trans, ?string $locale = null): string
    {
        return $trans->trans('enum.typeGame.' . $this->toTransKey(), locale: $locale);
    }

    // Convert to key used in the translations yaml
    public function toTransKey(): string
    {
        return match ($this) {
            self::GAME => 'game',
            self::DLC => 'dlc',
            self::DEMO => 'demo',
            self::OTHER => 'other',
        };
    }

    // Determine the type of game from steam value
    public static function fromSteam(string $type): TypeGameEnum
    {
        return match ($type) {
            'game' => self::GAME,
            'dlc' => self::DLC,
            'demo' => self::DEMO,
            default => self::OTHER,
        };
    }
}
