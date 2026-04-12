<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CartItemRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CART_VARIANT', fields: ['cart', 'productVariant'])]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductVariant $productVariant = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'La quantité doit être supérieure à 0.')]
    private int $quantity = 1;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getProductVariant(): ?ProductVariant
    {
        return $this->productVariant;
    }

    public function setProductVariant(ProductVariant $productVariant): static
    {
        $this->productVariant = $productVariant;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSubtotal(): int
    {
        return $this->productVariant->getProduct()->getPrice() * $this->quantity;
    }
}
