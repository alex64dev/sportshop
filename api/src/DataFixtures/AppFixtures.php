<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Enum\ProductCategory;

use function array_slice;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();

        $products = [
            ['name' => 'Maillot Domicile 2025', 'category' => ProductCategory::MAILLOT, 'price' => 4999, 'description' => 'Maillot officiel domicile saison 2024-2025. Tissu respirant et coupe ajustée.', 'sizes' => ['S', 'M', 'L', 'XL']],
            ['name' => 'Maillot Extérieur 2025', 'category' => ProductCategory::MAILLOT, 'price' => 4999, 'description' => 'Maillot officiel extérieur saison 2024-2025. Design moderne avec détails contrastés.', 'sizes' => ['S', 'M', 'L', 'XL']],
            ['name' => 'Maillot Enfant Domicile', 'category' => ProductCategory::MAILLOT, 'price' => 3999, 'description' => 'Maillot domicile pour les jeunes joueurs. Tailles enfant disponibles.', 'sizes' => ['8 ans', '10 ans', '12 ans', '14 ans']],
            ['name' => 'Short Officiel Noir', 'category' => ProductCategory::SHORT, 'price' => 2499, 'description' => 'Short officiel du club. Léger et confortable pour le jeu.', 'sizes' => ['S', 'M', 'L', 'XL']],
            ['name' => 'Short Officiel Blanc', 'category' => ProductCategory::SHORT, 'price' => 2499, 'description' => 'Short officiel coloris blanc. Idéal pour les matchs extérieur.', 'sizes' => ['S', 'M', 'L', 'XL']],
            ['name' => 'Survêtement Club Complet', 'category' => ProductCategory::SURVETEMENT, 'price' => 7999, 'description' => 'Survêtement complet veste + pantalon aux couleurs du club. Parfait pour l\'échauffement.', 'sizes' => ['S', 'M', 'L', 'XL', 'XXL']],
            ['name' => 'Veste Coupe-vent', 'category' => ProductCategory::SURVETEMENT, 'price' => 4499, 'description' => 'Veste coupe-vent imperméable avec logo du club brodé.', 'sizes' => ['S', 'M', 'L', 'XL']],
            ['name' => 'Sweat à Capuche Club', 'category' => ProductCategory::SURVETEMENT, 'price' => 3999, 'description' => 'Sweat à capuche molletonné avec le blason du club. Chaud et confortable.', 'sizes' => ['S', 'M', 'L', 'XL', 'XXL']],
            ['name' => 'Ballon Officiel Select', 'category' => ProductCategory::ACCESSOIRE, 'price' => 3499, 'description' => 'Ballon de handball Select taille 3 réplique officielle.', 'sizes' => ['Taille 0', 'Taille 1', 'Taille 2', 'Taille 3']],
            ['name' => 'Sac de Sport Club', 'category' => ProductCategory::ACCESSOIRE, 'price' => 2999, 'description' => 'Sac de sport 50L avec compartiment chaussures et logo du club.', 'sizes' => ['Unique']],
            ['name' => 'Gourde Isotherme', 'category' => ProductCategory::GOODIES, 'price' => 1499, 'description' => 'Gourde isotherme 750ml en acier inoxydable avec logo du club.', 'sizes' => ['Unique']],
            ['name' => 'Casquette Club', 'category' => ProductCategory::GOODIES, 'price' => 1999, 'description' => 'Casquette ajustable avec logo brodé du club.', 'sizes' => ['Unique']],
            ['name' => 'Écharpe Supporter', 'category' => ProductCategory::GOODIES, 'price' => 1299, 'description' => 'Écharpe tricotée aux couleurs du club. Indispensable en tribune.', 'sizes' => ['Unique']],
            ['name' => 'Protège-tibias Handball', 'category' => ProductCategory::ACCESSOIRE, 'price' => 1999, 'description' => 'Protège-tibias légers spécialement conçus pour le handball.', 'sizes' => ['S', 'M', 'L']],
            ['name' => 'Genouillères Renforcées', 'category' => ProductCategory::ACCESSOIRE, 'price' => 2499, 'description' => 'Genouillères avec rembourrage renforcé pour une protection optimale.', 'sizes' => ['S', 'M', 'L', 'XL']],
        ];

        foreach ($products as $index => $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setDescription($data['description']);
            $product->setSlug(strtolower((string) $slugger->slug($data['name'])));
            $product->setPrice($data['price']);
            $product->setCategory($data['category']);
            $product->setIsActive(true);

            $slug = strtoupper((string) $slugger->slug($data['name']));
            $words = explode('-', $slug);
            $skuPrefix = implode('-', array_slice($words, 0, 3));

            foreach ($data['sizes'] as $size) {
                $variant = new ProductVariant();
                $variant->setSize($size);
                $variant->setStock(random_int(5, 50));
                $skuSize = strtoupper(str_replace(' ', '', $size));
                $variant->setSku($skuPrefix . '-' . $skuSize);
                $product->addVariant($variant);
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
