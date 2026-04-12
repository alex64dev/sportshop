<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Cart\AddToCartRequest;
use App\Dto\Cart\CartResponse;
use App\Dto\Cart\UpdateCartItemRequest;
use App\Service\CartService;

use function count;
use function is_array;

use const JSON_UNESCAPED_UNICODE;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_cart_show', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $sessionId = $this->getSessionId($request);
        $cart = $this->cartService->getCart($sessionId);

        if (!$cart) {
            return $this->json(CartResponse::empty());
        }

        return $this->json(CartResponse::fromEntity($cart));
    }

    #[Route('/items', name: 'api_cart_add_item', methods: ['POST'])]
    public function addItem(Request $request): JsonResponse
    {
        $sessionId = $this->getSessionId($request);
        $dto = $this->deserialize($request, AddToCartRequest::class);

        $cart = $this->cartService->getOrCreateCart($sessionId);
        $this->cartService->addItem($cart, $dto->variantId, $dto->quantity);

        return $this->json(CartResponse::fromEntity($cart), Response::HTTP_CREATED);
    }

    #[Route('/items/{id}', name: 'api_cart_update_item', methods: ['PATCH'])]
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $sessionId = $this->getSessionId($request);
        $dto = $this->deserialize($request, UpdateCartItemRequest::class);

        $cart = $this->cartService->getOrCreateCart($sessionId);
        $this->cartService->updateQuantity($cart, $id, $dto->quantity);

        return $this->json(CartResponse::fromEntity($cart));
    }

    #[Route('/items/{id}', name: 'api_cart_remove_item', methods: ['DELETE'])]
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $sessionId = $this->getSessionId($request);
        $cart = $this->cartService->getOrCreateCart($sessionId);
        $this->cartService->removeItem($cart, $id);

        return $this->json(CartResponse::fromEntity($cart));
    }

    private function getSessionId(Request $request): string
    {
        $sessionId = $request->headers->get('X-Cart-Session');
        if (!$sessionId || 1 !== preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $sessionId)) {
            throw new BadRequestHttpException('Header X-Cart-Session manquant ou invalide (UUID v4 attendu).');
        }

        return $sessionId;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function deserialize(Request $request, string $class): object
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadRequestHttpException('Corps de la requête invalide (JSON attendu).');
        }

        $dto = new $class();
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->{$key} = $value;
            }
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new BadRequestHttpException(json_encode($messages, JSON_UNESCAPED_UNICODE));
        }

        return $dto;
    }
}
