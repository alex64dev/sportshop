<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Product;
use DateTimeImmutable;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;

use const ENT_HTML5;
use const ENT_QUOTES;
use const PATHINFO_BASENAME;
use const PATHINFO_EXTENSION;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HtmlSanitizerInterface $htmlSanitizer,
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public')] private string $publicDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'onBeforeEntityPersisted',
            BeforeEntityUpdatedEvent::class => 'onBeforeEntityUpdated',
        ];
    }

    public function onBeforeEntityPersisted(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (method_exists($entity, 'setCreatedAt') && null === $entity->getCreatedAt()) {
            $entity->setCreatedAt(new DateTimeImmutable());
        }

        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new DateTimeImmutable());
        }

        $this->handleSlug($entity);
        $this->handleVariantSkus($entity);
        $this->convertImageToWebp($entity);
        $this->sanitizeHtmlFields($entity);
    }

    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new DateTimeImmutable());
        }

        $this->handleSlug($entity);
        $this->handleVariantSkus($entity);
        $this->convertImageToWebp($entity);
        $this->sanitizeHtmlFields($entity);
    }

    private function handleSlug(object $entity): void
    {
        if (!$entity instanceof Product) {
            return;
        }

        if (!$entity->getSlug() && $entity->getName()) {
            $entity->setSlug(strtolower((string) $this->slugger->slug($entity->getName())));
        }
    }

    private function handleVariantSkus(object $entity): void
    {
        if (!$entity instanceof Product) {
            return;
        }

        $baseSlug = strtoupper((string) $this->slugger->slug($entity->getName() ?? ''));

        foreach ($entity->getVariants() as $variant) {
            if ($variant->getSku()) {
                continue;
            }

            $parts = [$baseSlug];

            if ($variant->getColor()) {
                $parts[] = strtoupper((string) $this->slugger->slug($variant->getColor()));
            }

            if ($variant->getSize()) {
                $parts[] = strtoupper(str_replace(' ', '', $variant->getSize()));
            }

            $variant->setSku(implode('-', $parts));
        }
    }

    private function convertImageToWebp(object $entity): void
    {
        if (!$entity instanceof Product) {
            return;
        }

        $imagePath = $entity->getImagePath();
        if (!$imagePath) {
            return;
        }

        $fullPath = $this->publicDir . '/uploads/products/' . $imagePath;
        if (!file_exists($fullPath)) {
            return;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if ('webp' === $extension) {
            return;
        }

        $image = match ($extension) {
            'jpg', 'jpeg' => imagecreatefromjpeg($fullPath),
            'png' => imagecreatefrompng($fullPath),
            default => null,
        };

        if (!$image) {
            return;
        }

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $fullPath);
        imagewebp($image, $webpPath, 80);
        imagedestroy($image);

        // Supprime l'original
        unlink($fullPath);

        // Met à jour le chemin en base
        $webpFilename = pathinfo($webpPath, PATHINFO_BASENAME);
        $entity->setImagePath($webpFilename);
    }

    private function sanitizeHtmlFields(object $entity): void
    {
        if ($entity instanceof Product && $entity->getDescription()) {
            $decoded = html_entity_decode($entity->getDescription(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $entity->setDescription($this->htmlSanitizer->sanitize($decoded));
        }
    }
}
