<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\AccessControl\Permission\Config\UserPermissionStorage;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * EasyAdmin CRUD controller for the User entity.
 *
 * Permissions are enforced via Symfony security attributes so that
 * the PermissionVoter checks each operation at runtime.
 */
#[IsGranted(UserPermissionStorage::USER_VIEW)]
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setPageTitle(Crud::PAGE_INDEX, 'Users')
            ->setPageTitle(Crud::PAGE_DETAIL, 'User Details')
            ->setPageTitle(Crud::PAGE_EDIT, 'Edit User')
            ->setDefaultSort(['lastLogin' => 'DESC'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('username')
            ->add('email')
            ->add('lastLogin')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex()
                ->setLabel('ID'),
            TextField::new('keycloakId', 'Keycloak ID')
                ->onlyOnDetail(),
            TextField::new('username', 'Username')
                ->setRequired(true),
            EmailField::new('email', 'Email')
                ->setRequired(false),
            // TextField::new('roles', 'Roles')
            //     ->formatValue(fn (array $value) => implode(', ', $value))
            //     ->setTemplatePath('admin/fields/roles.html.twig'),
            DateTimeField::new('lastLogin', 'Last Login')
                ->onlyOnIndex(),
            DateTimeField::new('lastLogin', 'Last Login')
                ->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewUser = Action::new('viewUser', 'View', 'fa fa-eye')
            ->linkToCrudAction('detail')
            ->setCssClass('btn btn-sm btn-secondary');

        return $actions
            ->add(Crud::PAGE_INDEX, $viewUser)
            ->setPermission(Action::DELETE, UserPermissionStorage::USER_DELETE)
            ->setPermission(Action::EDIT, UserPermissionStorage::USER_MANAGE)
            ->setPermission(Action::NEW, UserPermissionStorage::USER_MANAGE)
            ->setPermission(Action::BATCH_DELETE, UserPermissionStorage::USER_DELETE)
        ;
    }
}
