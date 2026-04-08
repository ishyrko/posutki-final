<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Article\Entity\Article;
use App\Domain\Shared\ValueObject\Slug;
use App\Domain\User\Entity\User;
use App\Infrastructure\Service\FileUploader;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Doctrine\ORM\EntityManagerInterface;

class ArticleCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly FileUploader $fileUploader,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Статья')
            ->setEntityLabelInPlural('Статьи')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['title', 'content', 'excerpt']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js')
            ->addJsFile('js/admin-article-tinymce.js');
    }

    public function createEntity(string $entityFqcn): Article
    {
        /** @var User $user */
        $user = $this->getUser();

        return new Article(
            $user->getId(),
            '',
            Slug::fromString('draft-' . time()),
            '',
            '',
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $request->request->all('Article') ?: $request->request->all();

        if (isset($formData['slugText']) && $formData['slugText'] !== '') {
            $entityInstance->setSlugFromString($formData['slugText']);
        }

        if (isset($formData['coverImage']) && is_string($formData['coverImage']) && $formData['coverImage'] !== '') {
            $coverImage = $formData['coverImage'];
            if (!str_starts_with($coverImage, '/')) {
                $entityInstance->setCoverImage('/uploads/articles/' . ltrim($coverImage, '/'));
            }
        }

        parent::persistEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Article) {
            $this->syncArticleCoverImageAfterAdminUpload($entityManager, $entityInstance);
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $request->request->all('Article') ?: $request->request->all();

        if (isset($formData['slugText']) && $formData['slugText'] !== '') {
            $entityInstance->setSlugFromString($formData['slugText']);
        }

        if (isset($formData['coverImage']) && is_string($formData['coverImage']) && $formData['coverImage'] !== '') {
            $coverImage = $formData['coverImage'];
            if (!str_starts_with($coverImage, '/')) {
                $entityInstance->setCoverImage('/uploads/articles/' . ltrim($coverImage, '/'));
            }
        }

        parent::updateEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Article) {
            $this->syncArticleCoverImageAfterAdminUpload($entityManager, $entityInstance);
        }
    }

    private function syncArticleCoverImageAfterAdminUpload(EntityManagerInterface $entityManager, Article $article): void
    {
        $cover = $article->getCoverImage();
        if ($cover === null || $cover === '' || !str_contains($cover, '/uploads/articles/')) {
            return;
        }

        $relative = 'articles/' . basename($cover);
        $newRelative = $this->fileUploader->processStoredArticleCoverImage($relative);
        if ($newRelative === null) {
            return;
        }

        $newUrl = '/uploads/' . $newRelative;
        if ($article->getCoverImage() !== $newUrl) {
            $article->setCoverImage($newUrl);
            $entityManager->flush();
        }
    }

    public function configureFields(string $pageName): iterable
    {
        $articleOnEdit = null;
        if ($pageName === Crud::PAGE_EDIT) {
            /** @var AdminContextProvider $contextProvider */
            $contextProvider = $this->container->get(AdminContextProvider::class);
            $articleCandidate = $contextProvider->getContext()?->getEntity()?->getInstance();
            if ($articleCandidate instanceof Article) {
                $articleOnEdit = $articleCandidate;
            }
        }

        yield TextField::new('id', 'ID')
            ->formatValue(fn ($value, $entity) => (string) $entity->getId()->getValue())
            ->hideOnForm();

        yield TextField::new('title', 'Заголовок');

        yield TextField::new('slug', 'Slug')
            ->formatValue(fn ($value, $entity) => (string) $entity->getSlug())
            ->hideOnForm();

        $slugTextField = TextField::new('slugText', 'Slug')
            ->setFormTypeOption('mapped', false)
            ->onlyOnForms()
            ->setHelp('Латиница, цифры и дефисы (например: moya-statya-2026)');

        if ($articleOnEdit instanceof Article) {
            $slugTextField->setFormTypeOption('data', (string) $articleOnEdit->getSlug());
        } else {
            $slugTextField->setFormTypeOption('data', '');
        }

        yield $slugTextField;

        yield ChoiceField::new('status', 'Статус')
            ->setChoices([
                'Черновик' => 'draft',
                'Опубликовано' => 'published',
                'Удалено' => 'deleted',
            ])
            ->renderAsBadges([
                'draft' => 'warning',
                'published' => 'success',
                'deleted' => 'danger',
            ]);

        yield IntegerField::new('views', 'Просмотры')
            ->hideOnForm();

        yield ArrayField::new('tags', 'Теги')
            ->hideOnIndex();

        yield TextareaField::new('excerpt', 'Отрывок')
            ->hideOnIndex();

        yield TextareaField::new('content', 'Контент')
            ->setFormTypeOption('attr', [
                'class' => 'ea-article-content-rte form-control',
                'rows' => 16,
            ])
            ->setHelp('Форматирование текста, заголовки, списки и ссылки. На сайте контент показывается как HTML.')
            ->hideOnIndex();

        yield AssociationField::new('category', 'Категория');

        $coverImageField = ImageField::new('coverImage', 'Обложка')
            ->setBasePath('/')
            ->setUploadDir('public/uploads/articles')
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setHelp('Загружайте файл, внешние URL не поддерживаются')
            ->hideOnIndex();

        if ($articleOnEdit instanceof Article && $articleOnEdit->getCoverImage()) {
            $imageUrl = $articleOnEdit->getCoverImage();
            $escapedImageUrl = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');

            $coverImageField
                ->setHelp(sprintf(
                    '<div style="margin-bottom:8px;">Текущее изображение:</div><a href="%1$s" target="_blank" rel="noopener noreferrer"><img src="%1$s" alt="Article cover" style="max-width:360px;width:100%%;height:auto;border-radius:8px;border:1px solid #ddd;" /></a><div style="margin-top:8px;">Загрузите новый файл, чтобы заменить текущее изображение.</div>',
                    $escapedImageUrl
                ))
                ->setFormTypeOption('help_html', true);
        }

        yield $coverImageField;

        yield TextField::new('authorId', 'Автор')
            ->formatValue(fn ($value, $entity) => (string) $entity->getAuthorId()->getValue())
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Создано')
            ->hideOnForm();

        yield DateTimeField::new('publishedAt', 'Опубликовано')
            ->hideOnForm()
            ->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Черновик' => 'draft',
                'Опубликовано' => 'published',
                'Удалено' => 'deleted',
            ]))
            ->add('createdAt')
            ->add('category');
    }
}
