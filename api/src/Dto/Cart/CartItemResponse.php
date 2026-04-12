<?php

declare(strict_types=1);

namespace App\Dto\Cart;

use App\Entity\CartItem;

class CartItemResponse
{
    public int $id;
    public int $variantId;
    public string $productName;
    public string $size;
    public ?string $color;
    public string $sku;
    public int $unitPrice;
    public int $quantity;
    public int $subtotal;
    public int $stockAvailable;
    public ?string $imagePath;

    public static function fromEntity(CartItem $item): self
    {
        $response = new self();
        $variant = $item->getProductVariant();
        $product = $variant->getProduct();

        $response->id = $item->getId();
        $response->variantId = $variant->getId();
        $response->productName = $product->getName();
        $response->size = $variant->getSize();
        $response->color = $variant->getColor();
        $response->sku = $variant->getSku();
        $response->unitPrice = $product->getPrice();
        $response->quantity = $item->getQuantity();
        $response->subtotal = $item->getSubtotal();
        $response->stockAvailable = $variant->getStock();
        $response->imagePath = $product->getImagePath();

        return $response;
    }
}
