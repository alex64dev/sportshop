<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::SUCCEEDED => 'Réussi',
            self::FAILED => 'Échoué',
            self::REFUNDED => 'Remboursé',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SUCCEEDED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'secondary',
        };
    }

    /** @return array<string, self> */
    public static function choices(): array
    {
        return [
            'En attente' => self::PENDING,
            'Réussi' => self::SUCCEEDED,
            'Échoué' => self::FAILED,
            'Remboursé' => self::REFUNDED,
        ];
    }
}
