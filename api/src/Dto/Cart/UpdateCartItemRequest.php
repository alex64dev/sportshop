<?php

declare(strict_types=1);

namespace App\Dto\Cart;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCartItemRequest
{
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être supérieure à 0.')]
    public ?int $quantity = null;
}
