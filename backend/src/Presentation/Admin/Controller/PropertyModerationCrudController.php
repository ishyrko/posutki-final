<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Property\ApproveRevision\ApproveRevisionCommand;
use App\Application\Command\Property\RejectRevision\RejectRevisionCommand;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Event\PropertyApprovedEvent;
use App\Domain\Property\Event\PropertyRejectedEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final class PropertyModerationCrudController extends PropertyCrudController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly MessageBusInterface $notificationBus,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Модерация объявления')
            ->setEntityLabelInPlural('Модерация объявлений');
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveRevision = Action::new('approveRevision', 'Одобрить ревизию')
            ->linkToCrudAction('approveRevision')
            ->displayIf(static fn(Property $property): bool => $property->getLatestPendingRevision() !== null)
            ->addCssClass('btn btn-success');

        $rejectRevision = Action::new('rejectRevision', 'Отклонить ревизию')
            ->linkToCrudAction('rejectRevision')
            ->displayIf(static fn(Property $property): bool => $property->getLatestPendingRevision() !== null)
            ->addCssClass('btn btn-danger');

        $approveProperty = Action::new('approveProperty', 'Одобрить объявление')
            ->linkToCrudAction('approveProperty')
            ->displayIf(static fn(Property $property): bool => $property->getLatestPendingRevision() === null && $property->getStatus() === 'moderation')
            ->addCssClass('btn btn-success');

        $rejectProperty = Action::new('rejectProperty', 'Отклонить объявление')
            ->linkToCrudAction('rejectProperty')
            ->displayIf(static fn(Property $property): bool => $property->getLatestPendingRevision() === null && $property->getStatus() === 'moderation')
            ->addCssClass('btn btn-danger');

        $viewDiff = Action::new('viewRevisionDiff', 'Показать diff')
            ->linkToCrudAction('viewRevisionDiff')
            ->displayIf(static fn(Property $property): bool => $property->getLatestPendingRevision() !== null);

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $viewDiff)
            ->add(Crud::PAGE_INDEX, $approveRevision)
            ->add(Crud::PAGE_INDEX, $rejectRevision)
            ->add(Crud::PAGE_INDEX, $approveProperty)
            ->add(Crud::PAGE_INDEX, $rejectProperty)
            ->add(Crud::PAGE_EDIT, $approveProperty)
            ->add(Crud::PAGE_EDIT, $rejectProperty)
            ->add(Crud::PAGE_EDIT, $viewDiff);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $rootAlias = $qb->getRootAliases()[0];

        return $qb
            ->andWhere(sprintf(
                '(%s.status IN (:moderationStatuses) OR EXISTS (
                    SELECT 1
                    FROM App\Domain\Property\Entity\PropertyRevision revision
                    WHERE revision.property = %s
                      AND revision.status = :pendingRevisionStatus
                ))',
                $rootAlias,
                $rootAlias
            ))
            ->setParameter('moderationStatuses', ['moderation', 'rejected'])
            ->setParameter('pendingRevisionStatus', 'pending');
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_EDIT) {
            yield TextareaField::new('pendingRevisionDataPretty', 'Новые данные ревизии')
                ->setFormTypeOption('disabled', true)
                ->setNumOfRows(16)
                ->setHelp('Снимок данных, который пользователь отправил на модерацию.');

            yield TextareaField::new('pendingRevisionDiff', 'Diff (текущее -> новое)')
                ->setFormTypeOption('disabled', true)
                ->setNumOfRows(12)
                ->setHelp('Сравнение текущей опубликованной версии и новой ревизии.');
        }

        foreach (parent::configureFields($pageName) as $field) {
            yield $field;
        }
    }

    public function approveRevision(AdminContext $context, Request $request): RedirectResponse
    {
        $property = $this->resolvePropertyFromContext($context, $request);
        if ($property === null) {
            $this->addFlash('warning', 'Действие доступно только из строки объявления');
            return $this->redirect($this->buildIndexUrl());
        }

        $revision = $property->getLatestPendingRevision();
        if ($revision === null) {
            $this->addFlash('warning', 'Для объявления нет ревизии на модерации');
            return $this->redirect($this->buildIndexUrl());
        }

        $this->commandBus->dispatch(new ApproveRevisionCommand(
            propertyId: (string) $property->getId()->getValue(),
            revisionId: (string) $revision->getId()->getValue(),
        ));

        $this->addFlash('success', 'Ревизия объявления одобрена');
        return $this->redirect($this->buildIndexUrl());
    }

    public function rejectRevision(AdminContext $context, Request $request): RedirectResponse
    {
        $property = $this->resolvePropertyFromContext($context, $request);
        if ($property === null) {
            $this->addFlash('warning', 'Действие доступно только из строки объявления');
            return $this->redirect($this->buildIndexUrl());
        }

        $revision = $property->getLatestPendingRevision();
        if ($revision === null) {
            $this->addFlash('warning', 'Для объявления нет ревизии на модерации');
            return $this->redirect($this->buildIndexUrl());
        }

        $this->commandBus->dispatch(new RejectRevisionCommand(
            propertyId: (string) $property->getId()->getValue(),
            revisionId: (string) $revision->getId()->getValue(),
            moderationComment: $property->getModerationComment(),
        ));

        $this->addFlash('success', 'Ревизия объявления отклонена');
        return $this->redirect($this->buildIndexUrl());
    }

    public function approveProperty(AdminContext $context, Request $request): RedirectResponse
    {
        $property = $this->resolvePropertyFromContext($context, $request);
        if ($property === null) {
            $this->addFlash('warning', 'Действие доступно только из строки объявления');
            return $this->redirect($this->buildIndexUrl());
        }

        if ($property->getLatestPendingRevision() !== null) {
            $this->addFlash('warning', 'Для объявления есть ревизия: используйте действия ревизии');
            return $this->redirect($this->buildIndexUrl());
        }

        if ($property->getStatus() !== 'moderation') {
            $this->addFlash('warning', 'Объявление не находится на первичной модерации');
            return $this->redirect($this->buildIndexUrl());
        }

        $property->approve();
        $this->propertyRepository->save($property);
        $this->notificationBus->dispatch(new PropertyApprovedEvent((string) $property->getId()->getValue()));

        $this->addFlash('success', 'Объявление одобрено');
        return $this->redirect($this->buildIndexUrl());
    }

    public function rejectProperty(AdminContext $context, Request $request): RedirectResponse
    {
        $property = $this->resolvePropertyFromContext($context, $request);
        if ($property === null) {
            $this->addFlash('warning', 'Действие доступно только из строки объявления');
            return $this->redirect($this->buildIndexUrl());
        }

        if ($property->getLatestPendingRevision() !== null) {
            $this->addFlash('warning', 'Для объявления есть ревизия: используйте действия ревизии');
            return $this->redirect($this->buildIndexUrl());
        }

        if ($property->getStatus() !== 'moderation') {
            $this->addFlash('warning', 'Объявление не находится на первичной модерации');
            return $this->redirect($this->buildIndexUrl());
        }

        $property->reject($property->getModerationComment());
        $this->propertyRepository->save($property);
        $this->notificationBus->dispatch(new PropertyRejectedEvent(
            (string) $property->getId()->getValue(),
            $property->getModerationComment()
        ));

        $this->addFlash('success', 'Объявление отклонено');
        return $this->redirect($this->buildIndexUrl());
    }

    public function viewRevisionDiff(AdminContext $context, Request $request): Response
    {
        $property = $this->resolvePropertyFromContext($context, $request);
        if ($property === null) {
            $this->addFlash('warning', 'Действие доступно только из строки объявления');
            return $this->redirect($this->buildIndexUrl());
        }

        return $this->render('admin/property_revision_diff.html.twig', [
            'property' => $property,
            'changes' => $property->getPendingRevisionChanges(),
            'pendingDataPretty' => $property->getPendingRevisionDataPretty(),
            'finalDataPretty' => $property->getPendingRevisionFinalDataPretty(),
            'currentImages' => $property->getImages(),
            'pendingImages' => (array) (($property->getPendingRevisionData()['images'] ?? []) ?: []),
            'backUrl' => $this->buildEditUrl($property),
        ]);
    }

    private function buildIndexUrl(): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
    }

    private function buildEditUrl(Property $property): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($property->getId()->getValue())
            ->generateUrl();
    }

    private function resolvePropertyFromContext(AdminContext $context, Request $request): ?Property
    {
        try {
            if ($context->getCrud() === null) {
                return $this->resolvePropertyFromRequest($request);
            }

            $entityDto = $context->getEntity();
            if ($entityDto === null) {
                return $this->resolvePropertyFromRequest($request);
            }

            $entity = $entityDto->getInstance();
            if ($entity instanceof Property) {
                return $entity;
            }

            return $this->resolvePropertyFromRequest($request);
        } catch (LogicException) {
            return $this->resolvePropertyFromRequest($request);
        }
    }

    private function resolvePropertyFromRequest(Request $request): ?Property
    {
        $entityId = $request->query->getString('entityId');
        if ($entityId === '') {
            $entityId = $request->query->getString('id');
        }

        if ($entityId === '') {
            return null;
        }

        try {
            return $this->propertyRepository->findById(Id::fromString($entityId));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
