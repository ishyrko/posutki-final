<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Article\Entity\ArticleCategory;
use App\Domain\Shared\ValueObject\Slug;
use App\Infrastructure\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\HttpFoundation\Request;

class ArticleCategoryCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SlugGenerator $slugGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ArticleCategory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Категория статей')
            ->setEntityLabelInPlural('Категории статей')
            ->setDefaultSort(['sortOrder' => 'ASC'])
            ->setSearchFields(['name', 'slug']);
    }

    public function createEntity(string $entityFqcn): ArticleCategory
    {
        $category = new ArticleCategory();
        $category->setName('');
        $category->setSlug('draft-' . time());
        $category->setSortOrder(0);

        return $category;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $this->getArticleCategoryFormData($request);

        if ($entityInstance instanceof ArticleCategory) {
            $this->applyArticleCategorySlugFromFormOrName($entityManager, $entityInstance, $formData);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $this->getArticleCategoryFormData($request);

        if ($entityInstance instanceof ArticleCategory) {
            $this->applyArticleCategorySlugFromFormOrName($entityManager, $entityInstance, $formData);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Uses manual slug when non-empty; otherwise generates from name, unique in article_categories.
     */
    private function applyArticleCategorySlugFromFormOrName(
        EntityManagerInterface $entityManager,
        ArticleCategory $category,
        array $formData,
    ): void {
        $slugText = isset($formData['slugText']) ? trim((string) $formData['slugText']) : '';
        if ($slugText !== '') {
            $category->setSlug(Slug::fromString($slugText)->getValue());

            return;
        }

        $base = $this->slugGenerator->generate($category->getName());
        if ($base === '') {
            $base = 'category-' . time();
        }

        $unique = $this->slugGenerator->ensureUniqueByPredicate(
            $base,
            fn (string $slug): bool => $this->isArticleCategorySlugTaken($entityManager, $slug, $category),
        );
        $category->setSlug(Slug::fromString($unique)->getValue());
    }

    private function isArticleCategorySlugTaken(
        EntityManagerInterface $entityManager,
        string $slug,
        ArticleCategory $category,
    ): bool {
        $existing = $entityManager->getRepository(ArticleCategory::class)->findOneBy(['slug' => $slug]);
        if ($existing === null) {
            return false;
        }

        return $existing !== $category;
    }

    private function getArticleCategoryFormData(?Request $request): array
    {
        if ($request === null) {
            return [];
        }

        return $request->request->all('ArticleCategory') ?: $request->request->all();
    }

    public function configureFields(string $pageName): iterable
    {
        $categoryOnEdit = null;
        if ($pageName === Crud::PAGE_EDIT) {
            /** @var AdminContextProvider $contextProvider */
            $contextProvider = $this->container->get(AdminContextProvider::class);
            $candidate = $contextProvider->getContext()?->getEntity()?->getInstance();
            if ($candidate instanceof ArticleCategory) {
                $categoryOnEdit = $candidate;
            }
        }

        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield TextField::new('name', 'Название');

        yield TextField::new('slug', 'Slug')
            ->formatValue(fn ($value, $entity) => (string) $entity->getSlug())
            ->hideOnForm();

        $slugTextField = TextField::new('slugText', 'Slug')
            ->setFormTypeOption('mapped', false)
            ->onlyOnForms()
            ->setHelp('Латиница, цифры и дефисы (например: novosti). Оставьте пустым — слаг будет собран из названия.');

        if ($categoryOnEdit instanceof ArticleCategory) {
            $slugTextField->setFormTypeOption('data', (string) $categoryOnEdit->getSlug());
        } else {
            $slugTextField->setFormTypeOption('data', '');
        }

        yield $slugTextField;

        yield IntegerField::new('sortOrder', 'Порядок сортировки')
            ->setHelp('Меньше = выше в списке');
    }
}
