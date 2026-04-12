<?php

declare(strict_types=1);

namespace App\Enum;

enum ProductCategory: string
{
    case MAILLOT = 'maillot';
    case SHORT = 'short';
    case SURVETEMENT = 'survetement';
    case GOODIES = 'goodies';
    case ACCESSOIRE = 'accessoire';

    public function getLabel(): string
    {
        return match ($this) {
            self::MAILLOT => 'Maillot',
            self::SHORT => 'Short',
            self::SURVETEMENT => 'Survêtement',
            self::GOODIES => 'Goodies',
            self::ACCESSOIRE => 'Accessoire',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::MAILLOT => 'primary',
            self::SHORT => 'info',
            self::SURVETEMENT => 'success',
            self::GOODIES => 'warning',
            self::ACCESSOIRE => 'secondary',
        };
    }

    /** @return array<string, self> */
    public static function choices(): array
    {
        return [
            'Maillot' => self::MAILLOT,
            'Short' => self::SHORT,
            'Survêtement' => self::SURVETEMENT,
            'Goodies' => self::GOODIES,
            'Accessoire' => self::ACCESSOIRE,
        ];
    }
}
