<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\StaticPageContentPersistNormalizer;
use App\Domain\Shared\ValueObject\Slug;
use App\Domain\StaticPage\Entity\StaticPage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Doctrine\ORM\EntityManagerInterface;

class StaticPageCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly StaticPageContentPersistNormalizer $staticPageContentPersistNormalizer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return StaticPage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Страница')
            ->setEntityLabelInPlural('Страницы')
            ->setDefaultSort(['slug' => 'ASC'])
            ->setSearchFields(['title', 'slug', 'content']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js')
            ->addJsFile('js/admin-article-tinymce.js');
    }

    public function createEntity(string $entityFqcn): StaticPage
    {
        return new StaticPage(
            Slug::fromString('draft-' . time()),
            '',
            '',
            null,
            null,
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $request->request->all('StaticPage') ?: $request->request->all();

        if ($entityInstance instanceof StaticPage && isset($formData['slugText']) && $formData['slugText'] !== '') {
            $entityInstance->setSlugFromString($formData['slugText']);
        }

        if ($entityInstance instanceof StaticPage) {
            $this->staticPageContentPersistNormalizer->normalize($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $request->request->all('StaticPage') ?: $request->request->all();

        if ($entityInstance instanceof StaticPage && isset($formData['slugText']) && $formData['slugText'] !== '') {
            $entityInstance->setSlugFromString($formData['slugText']);
        }

        if ($entityInstance instanceof StaticPage) {
            $this->staticPageContentPersistNormalizer->normalize($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        $pageOnEdit = null;
        if ($pageName === Crud::PAGE_EDIT) {
            /** @var AdminContextProvider $contextProvider */
            $contextProvider = $this->container->get(AdminContextProvider::class);
            $candidate = $contextProvider->getContext()?->getEntity()?->getInstance();
            if ($candidate instanceof StaticPage) {
                $pageOnEdit = $candidate;
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
            ->setHelp('Латиница, цифры и дефисы (например: politika-konfidentsialnosti)');

        if ($pageOnEdit instanceof StaticPage) {
            $slugTextField->setFormTypeOption('data', (string) $pageOnEdit->getSlug());
        } else {
            $slugTextField->setFormTypeOption('data', '');
        }

        yield $slugTextField;

        yield TextareaField::new('content', 'Контент')
            ->setFormTypeOption('attr', [
                'class' => 'ea-static-page-content-rte form-control',
                'rows' => 16,
                'data-upload-scope' => 'static-pages',
            ])
            ->setHelp('Форматирование текста, заголовки, списки, ссылки и изображения. Картинки загружаются на сервер, оптимизируются и на сайте показываются на всю ширину блока.')
            ->hideOnIndex();

        yield TextField::new('metaTitle', 'Meta title')
            ->hideOnIndex();

        yield TextareaField::new('metaDescription', 'Meta description')
            ->hideOnIndex();

        yield DateTimeField::new('updatedAt', 'Обновлено')
            ->hideOnForm();
    }
}
