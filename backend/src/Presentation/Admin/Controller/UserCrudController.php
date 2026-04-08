<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\User\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Пользователь')
            ->setEntityLabelInPlural('Пользователи')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['email', 'firstName', 'lastName', 'phone']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('id', 'ID')
            ->formatValue(fn ($value, $entity) => (string) $entity->getId()->getValue())
            ->hideOnForm();

        yield TextField::new('email', 'Email')
            ->formatValue(fn ($value, $entity) => $entity->getEmail()?->getValue() ?? '—')
            ->hideOnForm();

        yield TextField::new('firstName', 'Имя');
        yield TextField::new('lastName', 'Фамилия');
        yield TextField::new('phone', 'Телефон');

        yield BooleanField::new('isVerified', 'Верифицирован')
            ->renderAsSwitch(false);

        yield BooleanField::new('isPhoneVerified', 'Телефон подтвержден')
            ->renderAsSwitch(false);

        yield ArrayField::new('roles', 'Роли');

        yield ImageField::new('avatar', 'Аватар')
            ->setBasePath('/uploads')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Создан')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Обновлен')
            ->hideOnForm()
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isVerified')
            ->add('isPhoneVerified')
            ->add('createdAt');
    }
}
