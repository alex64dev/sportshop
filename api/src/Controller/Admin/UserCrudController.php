<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\AdminMailer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

use function in_array;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private AdminMailer $adminMailer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Administrateur')
            ->setEntityLabelInPlural('Administrateurs')
            ->setPageTitle('index', 'Gestion des administrateurs')
            ->setPageTitle('new', 'Ajout d\'un administrateur')
            ->setPageTitle('edit', 'Modifier l\'administrateur')
            ->setDefaultSort(['id' => 'ASC'])
            ->setFormOptions(['attr' => ['novalidate' => 'novalidate']]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, static fn (Action $action) => $action->setLabel('Ajouter un administrateur')->setIcon('fa fa-plus'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action) => $action->setLabel('Éditer')->setIcon('fa fa-pencil'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, static fn (Action $action) => $action->setLabel('Créer l\'administrateur'))
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->update(Crud::PAGE_INDEX, Action::DELETE, static fn (Action $action) => $action->displayIf(static fn ($entity) => !in_array('ROLE_SUPER_ADMIN', $entity->getRoles(), true)))
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield EmailField::new('email', 'Email');

        if (Crud::PAGE_NEW === $pageName || Crud::PAGE_EDIT === $pageName) {
            $passwordConstraints = [
                new Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                new Assert\Regex(pattern: '/[A-Z]/', message: 'Le mot de passe doit contenir au moins une majuscule.'),
                new Assert\Regex(pattern: '/[0-9]/', message: 'Le mot de passe doit contenir au moins un chiffre.'),
            ];

            $password = TextField::new('plainPassword', 'Mot de passe')
                ->setFormType(PasswordType::class)
                ->onlyOnForms();

            if (Crud::PAGE_NEW === $pageName) {
                $password
                    ->setFormTypeOptions([
                        'constraints' => [
                            new Assert\NotBlank(message: 'Le mot de passe est obligatoire.'),
                            ...$passwordConstraints,
                        ],
                    ])
                    ->setHelp('Min. 8 caractères, 1 majuscule, 1 chiffre')
                    ->setRequired(true);
            } else {
                $password
                    ->setFormTypeOptions([
                        'empty_data' => null,
                        'constraints' => $passwordConstraints,
                    ])
                    ->setHelp('Laisser vide pour ne pas modifier. Min. 8 caractères, 1 majuscule, 1 chiffre')
                    ->setRequired(false);
            }

            yield $password;
        }

        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Administrateur' => 'ROLE_ADMIN',
                'Super Administrateur' => 'ROLE_SUPER_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded()
            ->onlyOnIndex();

        yield BooleanField::new('isLocked', 'Compte bloqué')
            ->addCssClass('js-toggle');
    }

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param User $entityInstance
     */
    public function persistEntity($entityManager, $entityInstance): void
    {
        $entityInstance->setRoles(['ROLE_ADMIN']);
        $plainPassword = $entityInstance->getPlainPassword();
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);

        if ($plainPassword) {
            $this->adminMailer->sendNewAdminEmail($entityInstance, $plainPassword);
        }
    }

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param User $entityInstance
     */
    public function updateEntity($entityManager, $entityInstance): void
    {
        if (!in_array('ROLE_SUPER_ADMIN', $entityInstance->getRoles(), true)) {
            $entityInstance->setRoles(['ROLE_ADMIN']);
        }

        $this->hashPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPassword(User $user): void
    {
        if ($user->getPlainPassword()) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $user->getPlainPassword()),
            );
        }
    }
}
