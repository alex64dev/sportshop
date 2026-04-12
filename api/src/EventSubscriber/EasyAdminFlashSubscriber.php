<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;

use function in_array;

use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EasyAdminFlashSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'onEntityPersisted',
            AfterEntityUpdatedEvent::class => 'onEntityUpdated',
            AfterEntityDeletedEvent::class => 'onEntityDeleted',
        ];
    }

    public function onEntityPersisted(AfterEntityPersistedEvent $event): void
    {
        $label = $this->getEntityLabel($event->getEntityInstance());
        $this->addFlash('success', $this->buildMessage($label, 'créé'));
    }

    public function onEntityUpdated(AfterEntityUpdatedEvent $event): void
    {
        $label = $this->getEntityLabel($event->getEntityInstance());
        $this->addFlash('success', $this->buildMessage($label, 'modifié'));
    }

    public function onEntityDeleted(AfterEntityDeletedEvent $event): void
    {
        $label = $this->getEntityLabel($event->getEntityInstance());
        $this->addFlash('success', $this->buildMessage($label, 'supprimé'));
    }

    private function getEntityLabel(object $entity): string
    {
        return match ($entity::class) {
            'App\Entity\Product' => 'produit',
            'App\Entity\ProductVariant' => 'variante',
            'App\Entity\User' => 'administrateur',
            default => (new ReflectionClass($entity))->getShortName(),
        };
    }

    private function buildMessage(string $label, string $action): string
    {
        $feminine = in_array($label, ['variante', 'commande'], true);
        $article = in_array($label[0], ['a', 'e', 'i', 'o', 'u'], true) ? 'L\'' : ($feminine ? 'La ' : 'Le ');
        $suffix = $feminine ? 'e' : '';

        return $article . $label . ' a bien été ' . $action . $suffix;
    }

    private function addFlash(string $type, string $message): void
    {
        $session = $this->requestStack->getCurrentRequest()?->getSession();
        $session?->getFlashBag()->add($type, $message); // @phpstan-ignore method.notFound
    }
}
