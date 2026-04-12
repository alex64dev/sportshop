<?php

declare(strict_types=1);

namespace App\Dto\Cart;

use Symfony\Component\Validator\Constraints as Assert;

class AddToCartRequest
{
    #[Assert\NotNull(message: 'L\'identifiant de la variante est obligatoire.')]
    #[Assert\Positive]
    public ?int $variantId = null;

    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être supérieure à 0.')]
    public ?int $quantity = 1;
}
