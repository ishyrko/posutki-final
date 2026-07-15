<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Enum\PlacementPurchaseType;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementSlotRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PropertyPlacementPurchaseCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly PropertyPlacementService $placementService,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementSlotRepositoryInterface $slotRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PropertyPlacementPurchase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Заявка на размещение')
            ->setEntityLabelInPlural('Заявки на размещение')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['propertyId', 'note']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $activate = Action::new('activatePurchase', 'Активировать')
            ->linkToCrudAction('activatePurchase')
            ->displayIf(static fn(PropertyPlacementPurchase $p): bool => $p->isPendingPayment())
            ->addCssClass('btn btn-success');

        $reject = Action::new('rejectPurchase', 'Отклонить')
            ->linkToCrudAction('rejectPurchase')
            ->displayIf(static fn(PropertyPlacementPurchase $p): bool => $p->isPendingPayment())
            ->addCssClass('btn btn-danger');

        return $actions
            ->disable(Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $activate)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_DETAIL, $activate)
            ->add(Crud::PAGE_DETAIL, $reject);
    }

    public function createEntity(string $entityFqcn): PropertyPlacementPurchase
    {
        return new PropertyPlacementPurchase(
            propertyId: 0,
            ownerId: Id::fromInt(0),
            type: PlacementPurchaseType::Standard->value,
            durationMonths: 1,
            priceByn: 0,
            source: 'admin',
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof PropertyPlacementPurchase) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $property = $this->propertyRepository->findById(Id::fromInt($entityInstance->getPropertyId()));
        if ($property === null) {
            throw new \RuntimeException('Объявление не найдено');
        }

        $purchase = new PropertyPlacementPurchase(
            propertyId: $property->getId()->getValue(),
            ownerId: $property->getOwnerId(),
            type: $entityInstance->getType(),
            durationMonths: $entityInstance->getDurationMonths(),
            priceByn: $entityInstance->getPriceByn(),
            source: 'admin',
            slotId: $entityInstance->getSlotId(),
        );
        $purchase->setNote($entityInstance->getNote());
        $entityManager->persist($purchase);
        $entityManager->flush();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield IntegerField::new('propertyId', 'ID объявления')
            ->formatValue(function ($value, PropertyPlacementPurchase $purchase) {
                $property = $this->propertyRepository->findById(Id::fromInt($purchase->getPropertyId()));
                $title = $property?->getTitle();

                return $title !== null
                    ? sprintf('#%d — %s', $purchase->getPropertyId(), $title)
                    : ('#' . $purchase->getPropertyId());
            });
        yield ChoiceField::new('type', 'Тип')
            ->setChoices(PlacementPurchaseType::choices());
        yield IntegerField::new('slotId', 'Слот (id, для спецразмещения)')
            ->formatValue(function ($value, PropertyPlacementPurchase $purchase) {
                if ($purchase->getSlotId() === null) {
                    return '—';
                }
                $slot = $this->slotRepository->findById($purchase->getSlotId());

                return $slot !== null
                    ? sprintf('%s (id %d)', $slot->getLabel(), $slot->getId())
                    : ('#' . $purchase->getSlotId());
            })
            ->setRequired(false);
        yield IntegerField::new('durationMonths', 'Срок (мес)');
        yield IntegerField::new('priceByn', 'Сумма BYN');
        yield ChoiceField::new('status', 'Статус')
            ->setChoices(PlacementPurchaseStatus::choices())
            ->hideOnForm();
        yield TextField::new('source', 'Источник')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Создана')->hideOnForm();
        yield DateTimeField::new('activatedAt', 'Активирована')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('expiresAt', 'Истекает')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('reservationExpiresAt', 'Резерв до')->hideOnForm()->hideOnIndex();
        yield TextareaField::new('note', 'Комментарий')->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices(PlacementPurchaseStatus::choices()))
            ->add(ChoiceFilter::new('type')->setChoices(PlacementPurchaseType::choices()))
            ->add('createdAt');
    }

    public function activatePurchase(AdminContext $context): RedirectResponse
    {
        /** @var PropertyPlacementPurchase $purchase */
        $purchase = $context->getEntity()->getInstance();
        $property = $this->propertyRepository->findById(Id::fromInt($purchase->getPropertyId()));
        if ($property === null) {
            $this->addFlash('danger', 'Объявление не найдено');

            return $this->redirectToIndex();
        }

        $admin = $this->getUser();
        $adminId = $admin instanceof User ? $admin->getId() : null;

        try {
            $this->placementService->activatePurchase($purchase, $property, $adminId);
            $this->addFlash('success', 'Заявка активирована');
        } catch (\Throwable $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToIndex();
    }

    public function rejectPurchase(AdminContext $context): RedirectResponse
    {
        /** @var PropertyPlacementPurchase $purchase */
        $purchase = $context->getEntity()->getInstance();
        try {
            $purchase->reject('Отклонено администратором');
            $this->purchaseRepository->save($purchase);
            $this->addFlash('success', 'Заявка отклонена');
        } catch (\Throwable $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToIndex();
    }

    private function redirectToIndex(): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }
}
