<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Service\PhoneNumberNormalizer;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class UserCrudController extends AbstractCrudController
{
    /** @var array<string, string> */
    private const PROPERTY_STATUS_LABELS = [
        'draft' => 'Черновик',
        'moderation' => 'Ожидает модерации',
        'rejected' => 'Отклонено',
        'published' => 'Опубликовано',
        'archived' => 'В архиве',
        'deleted' => 'Удалено',
    ];

    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

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
            ->setSearchFields(['email', 'firstName', 'lastName', 'phone'])
            ->setDefaultRowAction(Action::DETAIL);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $phone = $entityInstance->getPhone();
            if ($phone !== null) {
                $entityInstance->setPhone(PhoneNumberNormalizer::normalize($phone));
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('id', 'ID')
            ->formatValue(fn ($value, $entity) => (string) $entity->getId()->getValue())
            ->hideOnForm();

        yield TextField::new('email', 'Email')
            ->formatValue(fn ($value, $entity) => $entity->getEmail()?->getValue() ?? '—')
            ->hideOnForm();

        yield TextField::new('firstName', 'Имя')
            ->setFormTypeOption('required', false);

        yield TextField::new('lastName', 'Фамилия')
            ->setFormTypeOption('required', false);
        yield TextField::new('phone', 'Телефон');

        yield BooleanField::new('isVerified', 'Верифицирован')
            ->renderAsSwitch(false);

        yield BooleanField::new('isPhoneVerified', 'Телефон подтвержден')
            ->renderAsSwitch(false);

        yield BooleanField::new('hasUsedFreePlacementTrial', 'Бесплатный VIP 1 использован')
            ->renderAsSwitch(false)
            ->setHelp('Один бесплатный VIP 1 на 2 недели на аккаунт');

        yield ArrayField::new('roles', 'Роли');

        yield ImageField::new('avatar', 'Аватар')
            ->setBasePath('/uploads')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Создан')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Обновлен')
            ->hideOnForm()
            ->hideOnIndex();

        yield TextField::new('adminPropertiesList', 'Объявления')
            ->onlyOnDetail()
            ->setValue('')
            ->formatValue(fn ($value, User $user): string => $this->formatPropertiesList($user))
            ->renderAsHtml();
    }

    private function formatPropertiesList(User $user): string
    {
        $ownerId = (string) $user->getId()->getValue();
        $totalCount = $this->propertyRepository->countByOwner($ownerId);
        if ($totalCount === 0) {
            return '<p class="text-muted mb-0">У пользователя нет объявлений.</p>';
        }

        $properties = $this->propertyRepository->findByOwner($ownerId, 1, $totalCount);
        $rows = array_map(
            fn (Property $property): string => $this->formatPropertyRow($property),
            $properties,
        );

        return sprintf(
            '<div class="table-responsive"><table class="table table-striped table-sm align-middle mb-0"><thead><tr><th>ID</th><th>Заголовок</th><th>Тип</th><th>Статус</th><th>Цена</th><th>Создано</th></tr></thead><tbody>%s</tbody></table></div>',
            implode('', $rows),
        );
    }

    private function formatPropertyRow(Property $property): string
    {
        $propertyId = (string) $property->getId()->getValue();
        $title = htmlspecialchars($property->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $url = htmlspecialchars($this->buildPropertyEditUrl($propertyId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $type = htmlspecialchars($this->formatPropertyType($property->getType()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $status = htmlspecialchars(
            self::PROPERTY_STATUS_LABELS[$property->getStatus()] ?? $property->getStatus(),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
        $price = htmlspecialchars($property->getPrice()->getFormatted(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $createdAt = htmlspecialchars(
            $property->getCreatedAt()->format('d.m.Y H:i'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        return sprintf(
            '<tr><td><a href="%s">#%s</a></td><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $url,
            $propertyId,
            $url,
            $title,
            $type,
            $status,
            $price,
            $createdAt,
        );
    }

    private function buildPropertyEditUrl(string $propertyId): string
    {
        return $this->adminUrlGenerator
            ->setController(PropertyCrudController::class)
            ->setAction(Action::EDIT)
            ->setEntityId($propertyId)
            ->generateUrl();
    }

    private function formatPropertyType(string $type): string
    {
        return PropertyType::tryFrom($type)?->label() ?? $type;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isVerified')
            ->add('isPhoneVerified')
            ->add('hasUsedFreePlacementTrial')
            ->add('createdAt');
    }
}
