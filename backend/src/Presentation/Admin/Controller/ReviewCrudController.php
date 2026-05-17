<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Review\Entity\Review;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Review\ValueObject\ReviewStatus;
use App\Domain\Shared\ValueObject\Id;
use LogicException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReviewCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Отзыв')
            ->setEntityLabelInPlural('Отзывы')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['text', 'moderationComment']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'На модерации' => ReviewStatus::Pending->value,
                'Одобрен' => ReviewStatus::Approved->value,
                'Отклонён' => ReviewStatus::Rejected->value,
            ]));
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approveReview', 'Одобрить')
            ->linkToCrudAction('approveReview')
            ->displayIf(static fn (Review $r): bool => $r->getStatus() === ReviewStatus::Pending)
            ->addCssClass('btn btn-success');

        $reject = Action::new('rejectReview', 'Отклонить')
            ->linkToCrudAction('rejectReview')
            ->displayIf(static fn (Review $r): bool => $r->getStatus() === ReviewStatus::Pending)
            ->addCssClass('btn btn-danger');

        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_DETAIL, $approve)
            ->add(Crud::PAGE_DETAIL, $reject)
            ->add(Crud::PAGE_EDIT, $approve)
            ->add(Crud::PAGE_EDIT, $reject);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('id', 'ID')
            ->onlyOnIndex()
            ->onlyOnDetail()
            ->formatValue(static fn ($value, Review $r): string => (string) ($r->getId()?->getValue() ?? ''));

        yield AssociationField::new('property', 'Объявление')
            ->formatValue(static fn ($value, Review $r): string => $r->getProperty()->getTitle() . ' (#' . $r->getProperty()->getId()->getValue() . ')')
            ->setFormTypeOption('disabled', true);

        yield AssociationField::new('author', 'Автор')
            ->setFormTypeOption('disabled', true);

        yield IntegerField::new('rating', 'Оценка');

        yield TextareaField::new('text', 'Текст');

        yield ChoiceField::new('status', 'Статус')
            ->setChoices([
                'На модерации' => ReviewStatus::Pending->value,
                'Одобрен' => ReviewStatus::Approved->value,
                'Отклонён' => ReviewStatus::Rejected->value,
            ]);

        yield TextareaField::new('moderationComment', 'Комментарий модерации');

        yield DateTimeField::new('createdAt', 'Создан')->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Обновлён')->hideOnForm();
    }

    public function approveReview(AdminContext $context, Request $request): RedirectResponse
    {
        $entity = $this->resolveReviewFromContext($context, $request);
        if ($entity === null) {
            $this->addFlash('warning', 'Не удалось определить отзыв');

            return $this->redirectToIndex();
        }

        if ($entity->getStatus() !== ReviewStatus::Pending) {
            $this->addFlash('warning', 'Отзыв уже обработан');

            return $this->redirectToIndex();
        }

        $entity->approve();
        $this->reviewRepository->save($entity);
        $this->addFlash('success', 'Отзыв одобрен');

        return $this->redirectToIndex();
    }

    public function rejectReview(AdminContext $context, Request $request): Response
    {
        $entity = $this->resolveReviewFromContext($context, $request);
        if ($entity === null) {
            $this->addFlash('warning', 'Не удалось определить отзыв');

            return $this->redirectToIndex();
        }

        if ($entity->getStatus() !== ReviewStatus::Pending) {
            $this->addFlash('warning', 'Отзыв уже обработан');

            return $this->redirectToIndex();
        }

        if ($request->isMethod('POST')) {
            $comment = $request->request->getString('moderationComment');
            $entity->reject($comment !== '' ? $comment : null);
            $this->reviewRepository->save($entity);
            $this->addFlash('success', 'Отзыв отклонён');

            return $this->redirectToIndex();
        }

        return $this->render('admin/review_reject.html.twig', [
            'review' => $entity,
            'cancelUrl' => $this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl(),
        ]);
    }

    private function redirectToIndex(): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl()
        );
    }

    private function resolveReviewFromContext(AdminContext $context, Request $request): ?Review
    {
        try {
            if ($context->getCrud() === null) {
                return $this->resolveReviewFromRequest($request);
            }

            $entityDto = $context->getEntity();
            $entity = $entityDto->getInstance();
            if ($entity instanceof Review) {
                return $entity;
            }

            return $this->resolveReviewFromRequest($request);
        } catch (LogicException) {
            return $this->resolveReviewFromRequest($request);
        }
    }

    private function resolveReviewFromRequest(Request $request): ?Review
    {
        $entityId = $request->query->getString('entityId');
        if ($entityId === '') {
            $entityId = $request->query->getString('id');
        }

        if ($entityId === '') {
            return null;
        }

        try {
            return $this->reviewRepository->findById(Id::fromString($entityId));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
