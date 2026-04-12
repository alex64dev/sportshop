<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\ProductVariant;
use App\Repository\CartRepository;
use App\Repository\ProductVariantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CartService
{
    private const CART_TTL_DAYS = 7;

    public function __construct(
        private EntityManagerInterface $em,
        private CartRepository $cartRepository,
        private ProductVariantRepository $variantRepository,
    ) {
    }

    public function getOrCreateCart(string $sessionId): Cart
    {
        $cart = $this->cartRepository->findBySessionId($sessionId);

        if ($cart && $cart->isExpired()) {
            $this->em->remove($cart);
            $this->em->flush();
            $cart = null;
        }

        if (!$cart) {
            $cart = new Cart();
            $cart->setSessionId($sessionId);
            $cart->setExpiresAt(new DateTimeImmutable('+' . self::CART_TTL_DAYS . ' days'));
            $this->em->persist($cart);
            $this->em->flush();
        }

        return $cart;
    }

    public function getCart(string $sessionId): ?Cart
    {
        $cart = $this->cartRepository->findBySessionId($sessionId);

        if ($cart && $cart->isExpired()) {
            $this->em->remove($cart);
            $this->em->flush();

            return null;
        }

        return $cart;
    }

    public function addItem(Cart $cart, int $variantId, int $quantity): CartItem
    {
        $variant = $this->findVariantOrFail($variantId);
        $this->assertProductActive($variant);
        $this->assertStockAvailable($variant, $quantity, $cart);

        // Merge si la variante est déjà dans le panier
        foreach ($cart->getItems() as $existingItem) {
            if ($existingItem->getProductVariant()->getId() === $variantId) {
                $newQuantity = $existingItem->getQuantity() + $quantity;
                $this->assertStockAvailable($variant, $newQuantity);

                $existingItem->setQuantity($newQuantity);
                $this->refreshExpiration($cart);
                $this->em->flush();

                return $existingItem;
            }
        }

        $item = new CartItem();
        $item->setProductVariant($variant);
        $item->setQuantity($quantity);
        $cart->addItem($item);

        $this->refreshExpiration($cart);
        $this->em->flush();

        return $item;
    }

    public function updateQuantity(Cart $cart, int $itemId, int $quantity): CartItem
    {
        $item = $this->findCartItemOrFail($cart, $itemId);
        $this->assertStockAvailable($item->getProductVariant(), $quantity);

        $item->setQuantity($quantity);
        $this->refreshExpiration($cart);
        $this->em->flush();

        return $item;
    }

    public function removeItem(Cart $cart, int $itemId): void
    {
        $item = $this->findCartItemOrFail($cart, $itemId);
        $cart->removeItem($item);
        $this->em->remove($item);
        $this->em->flush();
    }

    private function findVariantOrFail(int $variantId): ProductVariant
    {
        $variant = $this->variantRepository->find($variantId);
        if (!$variant) {
            throw new NotFoundHttpException('Variante introuvable.');
        }

        return $variant;
    }

    private function assertProductActive(ProductVariant $variant): void
    {
        if (!$variant->getProduct()->isActive()) {
            throw new BadRequestHttpException('Ce produit n\'est plus disponible.');
        }
    }

    private function assertStockAvailable(ProductVariant $variant, int $requestedQuantity, ?Cart $excludeCart = null): void
    {
        $availableStock = $variant->getStock();

        // Si on ajoute à un panier existant, ne pas compter la quantité déjà dans le panier
        // pour la vérification initiale (le merge se fait après)
        if ($excludeCart) {
            foreach ($excludeCart->getItems() as $item) {
                if ($item->getProductVariant()->getId() === $variant->getId()) {
                    // On vérifiera le total après merge dans addItem
                    return;
                }
            }
        }

        if ($requestedQuantity > $availableStock) {
            if (0 === $availableStock) {
                throw new UnprocessableEntityHttpException('Ce produit est épuisé.');
            }

            throw new UnprocessableEntityHttpException("Stock insuffisant. Seulement {$availableStock} disponible(s).");
        }
    }

    private function findCartItemOrFail(Cart $cart, int $itemId): CartItem
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getId() === $itemId) {
                return $item;
            }
        }

        throw new NotFoundHttpException('Article introuvable dans le panier.');
    }

    private function refreshExpiration(Cart $cart): void
    {
        $cart->setExpiresAt(new DateTimeImmutable('+' . self::CART_TTL_DAYS . ' days'));
    }
}
