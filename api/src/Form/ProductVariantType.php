<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ProductVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVariantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('size', TextType::class, [
                'label' => 'Taille',
                'attr' => ['placeholder' => 'S, M, L, XL, 10 ans...'],
            ])
            ->add('color', TextType::class, [
                'label' => 'Couleur',
                'required' => false,
                'attr' => ['placeholder' => 'Optionnel'],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'data' => 0,
            ])
            ->add('sku', TextType::class, [
                'label' => 'Référence',
                'required' => false,
                'attr' => ['placeholder' => 'Générée automatiquement'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductVariant::class,
        ]);
    }
}
