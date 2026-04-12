<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductVariantRepository;
use App\Trait\CreatedAtTrait;
use App\Trait\UpdatedAtTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_VARIANT_SKU', fields: ['sku'])]
class ProductVariant
{
    use CreatedAtTrait;
    use UpdatedAtTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'La taille est obligatoire.')]
    private ?string $size = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $color = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le stock ne peut pas être négatif.')]
    private int $stock = 0;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le SKU est obligatoire.')]
    private ?string $sku = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function __toString(): string
    {
        $label = $this->size ?? '';
        if ($this->color) {
            $label .= ' — ' . $this->color;
        }

        return $label;
    }
}
