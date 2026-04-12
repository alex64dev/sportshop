<?php

declare(strict_types=1);

namespace App\Dto\Cart;

use App\Entity\Cart;

class CartResponse
{
    public int $itemCount;

    /** @var list<CartItemResponse> */
    public array $items;

    public int $total;

    public static function fromEntity(Cart $cart): self
    {
        $response = new self();
        $response->items = array_map(
            static fn ($item) => CartItemResponse::fromEntity($item),
            $cart->getItems()->toArray(),
        );
        $response->itemCount = array_sum(array_map(
            static fn (CartItemResponse $item) => $item->quantity,
            $response->items,
        ));
        $response->total = $cart->getTotal();

        return $response;
    }

    public static function empty(): self
    {
        $response = new self();
        $response->items = [];
        $response->itemCount = 0;
        $response->total = 0;

        return $response;
    }
}
