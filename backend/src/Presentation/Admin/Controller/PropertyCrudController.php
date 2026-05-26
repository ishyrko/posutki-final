<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Enum\SellerType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Property && $entityInstance->getPriceCurrency() === 'BYN') {
            $entityInstance->setPriceByn($entityInstance->getPriceAmount());
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('id', 'ID')
            ->formatValue(fn ($value, $entity) => (string) $entity->getId()->getValue())
            ->hideOnForm();

        yield FormField::addTab('Основное');

        yield TextField::new('title', 'Заголовок');

        yield ChoiceField::new('type', 'Тип')
            ->setChoices(PropertyType::choices());

        yield ChoiceField::new('dealType', 'Сделка')
            ->setChoices(DealType::choices());

        yield ChoiceField::new('sellerType', 'Тип продавца')
            ->setChoices([
                'Частное лицо' => SellerType::Individual->value,
                'Бизнес' => SellerType::Business->value,
            ])
            ->hideOnIndex();

        yield TextareaField::new('description', 'Описание')
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

        yield FormField::addTab('Цена и площади');

        yield IntegerField::new('priceAmount', 'Цена (сумма)')
            ->hideOnIndex();

        yield ChoiceField::new('priceCurrency', 'Валюта')
            ->setChoices(['BYN' => 'BYN', 'USD' => 'USD', 'RUB' => 'RUB', 'EUR' => 'EUR'])
            ->hideOnIndex();

        yield TextField::new('price', 'Цена')
            ->formatValue(fn ($value, $entity) => $entity->getPrice()->getFormatted())
            ->onlyOnIndex();

        yield IntegerField::new('priceByn', 'Цена в BYN (кэш)')
            ->hideOnIndex()
            ->setHelp('Пересчитывается автоматически при сохранении, если валюта BYN.');

        yield BooleanField::new('weekendPriceNegotiable', 'Цена на выходные договорная')
            ->hideOnIndex();

        yield NumberField::new('area', 'Площадь общая, м²')
            ->hideOnIndex();

        yield NumberField::new('landArea', 'Площадь участка, м²')
            ->hideOnIndex();

        yield NumberField::new('livingArea', 'Жилая площадь, м²')
            ->hideOnIndex();

        yield NumberField::new('kitchenArea', 'Площадь кухни, м²')
            ->hideOnIndex();

        yield IntegerField::new('rooms', 'Комнат');

        yield IntegerField::new('roomsInDeal', 'Комнат в сделке')
            ->hideOnIndex();

        yield NumberField::new('roomsArea', 'Площадь комнат в сделке, м²')
            ->hideOnIndex();

        yield FormField::addTab('Дом и посуточно');

        yield IntegerField::new('floor', 'Этаж')
            ->hideOnIndex();

        yield IntegerField::new('totalFloors', 'Этажей в доме')
            ->hideOnIndex();

        yield IntegerField::new('bathrooms', 'Санузлов')
            ->hideOnIndex();

        yield IntegerField::new('yearBuilt', 'Год постройки')
            ->hideOnIndex();

        yield ChoiceField::new('renovation', 'Ремонт')
            ->setChoices([
                'Без ремонта' => 'Без ремонта',
                'Требует ремонта' => 'Требует ремонта',
                'Косметический' => 'Косметический',
                'Хороший' => 'Хороший',
                'Евроремонт' => 'Евроремонт',
                'Дизайнерский' => 'Дизайнерский',
            ])
            ->hideOnIndex();

        yield ChoiceField::new('balcony', 'Балкон')
            ->setChoices([
                'Нет' => 'Нет',
                'Балкон' => 'Балкон',
                'Лоджия' => 'Лоджия',
                'Балкон и лоджия' => 'Балкон и лоджия',
            ])
            ->hideOnIndex();

        yield IntegerField::new('maxDailyGuests', 'Макс. гостей (посуточно)')
            ->hideOnIndex();

        yield IntegerField::new('dailySingleBeds', 'Односпальных кроватей')
            ->hideOnIndex();

        yield IntegerField::new('dailyDoubleBeds', 'Двуспальных кроватей')
            ->hideOnIndex();

        yield TextField::new('checkInTime', 'Время заезда (ЧЧ:ММ)')
            ->hideOnIndex();

        yield TextField::new('checkOutTime', 'Время выезда (ЧЧ:ММ)')
            ->hideOnIndex();

        yield FormField::addTab('Адрес и контакты');

        yield IntegerField::new('cityId', 'ID города')
            ->hideOnIndex();

        yield IntegerField::new('streetId', 'ID улицы (справочник)')
            ->hideOnIndex()
            ->setHelp('Если задан, свободное название улицы игнорируется.');

        yield TextField::new('streetName', 'Улица (свободный ввод)')
            ->hideOnIndex();

        yield TextField::new('addressBuilding', 'Дом')
            ->hideOnIndex();

        yield TextField::new('addressBlock', 'Корпус')
            ->hideOnIndex();

        yield NumberField::new('latitude', 'Широта')
            ->hideOnIndex();

        yield NumberField::new('longitude', 'Долгота')
            ->hideOnIndex();

        yield BooleanField::new('nearMetro', 'Рядом с метро')
            ->hideOnIndex();

        yield TextField::new('contactPhone', 'Контакт: телефон')
            ->hideOnIndex();

        yield TextField::new('contactName', 'Контакт: имя')
            ->hideOnIndex();

        yield TextField::new('instagramUrl', 'Instagram')
            ->hideOnIndex();

        yield TextField::new('websiteUrl', 'Сайт')
            ->hideOnIndex();

        yield FormField::addTab('Условия и медиа');

        yield ArrayField::new('dealConditions', 'Условия сделки')
            ->hideOnIndex();

        yield ArrayField::new('paymentMethods', 'Способы оплаты')
            ->hideOnIndex()
            ->setHelp('Например: cash, card, bank_transfer');

        yield ArrayField::new('additionalServices', 'Доп. услуги')
            ->hideOnIndex();

        yield ArrayField::new('externalCalendarUrls', 'Внешние календари (iCal)')
            ->hideOnIndex();

        yield ArrayField::new('images', 'Фото (URL)')
            ->hideOnIndex()
            ->setHelp('Список URL изображений.');

        yield ArrayField::new('amenities', 'Удобства')
            ->hideOnIndex();

        yield TextareaField::new('imagesDisplay', 'Фото (просмотр)')
            ->onlyOnForms()
            ->setFormTypeOption('disabled', true)
            ->setNumOfRows(6)
            ->setHelp('Только для просмотра. Редактируйте поле «Фото (URL)» выше.');

        yield FormField::addTab('Служебное');

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

        yield IntegerField::new('phoneViews', 'Просмотры телефона')
            ->hideOnForm()
            ->hideOnIndex();

        yield TextField::new('ownerId', 'Владелец')
            ->formatValue(fn ($value, $entity) => (string) $entity->getOwnerId()->getValue())
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Создано')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Обновлено')
            ->hideOnForm()
            ->hideOnIndex();

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
