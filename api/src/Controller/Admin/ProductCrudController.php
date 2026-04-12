<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Enum\ProductCategory;
use App\Form\ProductVariantType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle('index', 'Gestion des produits')
            ->setPageTitle('new', 'Ajouter un produit')
            ->setPageTitle('edit', 'Modifier le produit')
            ->setDefaultSort(['id' => 'DESC'])
            ->setFormOptions(['attr' => ['novalidate' => 'novalidate']]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, static fn (Action $action) => $action->setLabel('Ajouter un produit')->setIcon('fa fa-plus'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action) => $action->setLabel('Éditer')->setIcon('fa fa-pencil'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, static fn (Action $action) => $action->setLabel('Créer le produit'))
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug')->setTargetFieldName('name')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();

        yield ChoiceField::new('category', 'Catégorie')
            ->setChoices(ProductCategory::choices())
            ->renderAsBadges([
                'maillot' => 'primary',
                'short' => 'info',
                'survetement' => 'success',
                'goodies' => 'warning',
                'accessoire' => 'secondary',
            ]);

        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);

        yield ImageField::new('imagePath', 'Image')
            ->setBasePath('uploads/products')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false);

        yield BooleanField::new('isActive', 'Actif')
            ->hideOnForm()
            ->addCssClass('js-toggle');

        yield IntegerField::new('totalStock', 'Stock total')
            ->hideOnForm();

        yield CollectionField::new('variants', 'Variantes')
            ->setEntryType(ProductVariantType::class)
            ->allowAdd()
            ->allowDelete()
            ->setEntryIsComplex()
            ->hideOnIndex();
    }
}
