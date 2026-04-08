<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Enum\DealType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class PropertyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Property::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Объявление')
            ->setEntityLabelInPlural('Объявления')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['title', 'description', 'contactPhone', 'contactName']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('id', 'ID')
            ->formatValue(fn ($value, $entity) => (string) $entity->getId()->getValue())
            ->hideOnForm();

        yield TextField::new('title', 'Заголовок');

        yield ChoiceField::new('type', 'Тип')
            ->setChoices(PropertyType::choices());

        yield ChoiceField::new('dealType', 'Сделка')
            ->setChoices(DealType::choices());

        yield TextField::new('price', 'Цена')
            ->formatValue(fn ($value, $entity) => $entity->getPrice()->getFormatted())
            ->hideOnForm();

        yield NumberField::new('area', 'Площадь общая, м²')
            ->hideOnIndex();

        yield IntegerField::new('rooms', 'Комнат');

        yield IntegerField::new('roomsInDeal', 'Комнат в сделке')
            ->hideOnIndex();

        yield NumberField::new('roomsArea', 'Площадь комнат в сделке, м²')
            ->hideOnIndex();

        yield ChoiceField::new('status', 'Статус')
            ->setChoices([
                'Черновик' => 'draft',
                'Ожидает модерации' => 'moderation',
                'Отклонено' => 'rejected',
                'Опубликовано' => 'published',
                'В архиве' => 'archived',
                'Удалено' => 'deleted',
            ])
            ->renderAsBadges([
                'draft' => 'warning',
                'moderation' => 'info',
                'rejected' => 'danger',
                'published' => 'success',
                'archived' => 'secondary',
                'deleted' => 'danger',
            ]);

        yield TextareaField::new('moderationComment', 'Комментарий модератора')
            ->hideOnIndex()
            ->setHelp('Комментарий обязателен при отклонении объявления.');

        yield ChoiceField::new('pendingRevisionStatus', 'Ревизия')
            ->setChoices([
                'На проверке' => 'pending',
                'Отклонена' => 'rejected',
            ])
            ->renderAsBadges([
                'pending' => 'info',
                'rejected' => 'danger',
            ])
            ->hideOnForm();

        yield TextareaField::new('pendingRevisionComment', 'Комментарий к ревизии')
            ->hideOnIndex()
            ->hideOnForm();

        yield IntegerField::new('views', 'Просмотры')
            ->hideOnForm();

        yield TextField::new('ownerId', 'Владелец')
            ->formatValue(fn ($value, $entity) => (string) $entity->getOwnerId()->getValue())
            ->hideOnForm()
            ->hideOnIndex();

        yield TextField::new('contactPhone', 'Контакт: телефон')
            ->hideOnIndex();

        yield TextField::new('contactName', 'Контакт: имя')
            ->hideOnIndex();

        yield TextareaField::new('description', 'Описание')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Создано')
            ->hideOnForm();

        yield DateTimeField::new('publishedAt', 'Опубликовано')
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new('boostedAt', 'Поднятие в топ (бесплатное)')
            ->hideOnForm()
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Черновик' => 'draft',
                'Ожидает модерации' => 'moderation',
                'Отклонено' => 'rejected',
                'Опубликовано' => 'published',
                'В архиве' => 'archived',
                'Удалено' => 'deleted',
            ]))
            ->add(ChoiceFilter::new('type')->setChoices(PropertyType::choices()))
            ->add(ChoiceFilter::new('dealType')->setChoices(DealType::choices()))
            ->add('createdAt');
    }
}
