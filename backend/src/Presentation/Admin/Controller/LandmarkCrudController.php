<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Repository\LandmarkRepositoryInterface;
use App\Infrastructure\Service\FileUploader;
use App\Infrastructure\Service\SlugGenerator;
use App\Infrastructure\Service\YandexForwardGeocoder;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class LandmarkCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ArticleHtmlNormalizer $articleHtmlNormalizer,
        private readonly ArticleTextSanitizer $articleTextSanitizer,
        private readonly SlugGenerator $slugGenerator,
        private readonly LandmarkRepositoryInterface $landmarkRepository,
        private readonly FileUploader $fileUploader,
        private readonly YandexForwardGeocoder $forwardGeocoder,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Landmark::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Достопримечательность')
            ->setEntityLabelInPlural('Достопримечательности')
            ->setDefaultSort(['sortOrder' => 'ASC', 'name' => 'ASC'])
            ->setSearchFields(['name', 'slug']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js')
            ->addJsFile('js/admin-article-tinymce.js');
    }

    public function createEntity(string $entityFqcn): Landmark
    {
        return new Landmark(
            cityId: 1,
            name: '',
            slug: 'draft-' . time(),
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $request?->request->all('Landmark') ?: $request?->request->all() ?? [];

        if ($entityInstance instanceof Landmark && isset($formData['imageUrl']) && is_string($formData['imageUrl']) && $formData['imageUrl'] !== '') {
            $entityInstance->setImageUrl($formData['imageUrl']);
        }

        if ($entityInstance instanceof Landmark) {
            $this->applyLandmarkSlugFromFormOrName($entityInstance, $formData);
            $this->applyLandmarkFactsFromForm($entityInstance, $formData);
            $this->applyLandmarkGuestTipsFromForm($entityInstance, $formData);
            $this->normalizeLandmark($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Landmark) {
            $this->syncLandmarkImageAfterAdminUpload($entityManager, $entityInstance);
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $formData = $request?->request->all('Landmark') ?: $request?->request->all() ?? [];

        if ($entityInstance instanceof Landmark && isset($formData['imageUrl']) && is_string($formData['imageUrl']) && $formData['imageUrl'] !== '') {
            $entityInstance->setImageUrl($formData['imageUrl']);
        }

        if ($entityInstance instanceof Landmark) {
            $this->restoreLandmarkImageIfClearedWithoutIntent($entityManager, $entityInstance, $request);
            $this->applyLandmarkSlugFromFormOrName($entityInstance, $formData);
            $this->applyLandmarkFactsFromForm($entityInstance, $formData);
            $this->applyLandmarkGuestTipsFromForm($entityInstance, $formData);
            $this->normalizeLandmark($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Landmark) {
            $this->syncLandmarkImageAfterAdminUpload($entityManager, $entityInstance);
        }
    }

    public function configureFields(string $pageName): iterable
    {
        $landmarkOnEdit = null;
        if ($pageName === Crud::PAGE_EDIT) {
            /** @var AdminContextProvider $contextProvider */
            $contextProvider = $this->container->get(AdminContextProvider::class);
            $landmarkCandidate = $contextProvider->getContext()?->getEntity()?->getInstance();
            if ($landmarkCandidate instanceof Landmark) {
                $landmarkOnEdit = $landmarkCandidate;
            }
        }

        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield IntegerField::new('cityId', 'ID города');

        yield TextField::new('name', 'Название');

        yield TextField::new('nameGenitive', 'Название в родительном падеже')
            ->setHelp('Например: «Национальной библиотеки» — для заголовков «возле …» в каталоге и meta title')
            ->setFormTypeOption('required', true);

        yield TextField::new('slug', 'Slug')
            ->formatValue(static fn ($value, Landmark $entity): string => $entity->getSlug())
            ->hideOnForm();

        $slugTextField = TextField::new('slugText', 'Slug')
            ->setFormTypeOption('mapped', false)
            ->onlyOnForms()
            ->setHelp('Латиница, цифры и дефисы. Оставьте пустым — slug будет собран из названия.');

        if ($landmarkOnEdit instanceof Landmark) {
            $slugTextField->setFormTypeOption('data', $landmarkOnEdit->getSlug());
        } else {
            $slugTextField->setFormTypeOption('data', '');
        }

        yield $slugTextField;

        yield ChoiceField::new('category', 'Категория')
            ->setChoices([
                'Достопримечательность' => 'sight',
                'Вокзал / транспорт' => 'station',
                'Стадион / арена' => 'stadium',
                'Парк' => 'park',
                'Торговый центр' => 'mall',
            ])
            ->allowMultipleChoices(false)
            ->renderExpanded(false);

        yield NumberField::new('latitude', 'Широта')
            ->setNumDecimals(6)
            ->setRequired(false)
            ->setHelp('Необязательно: если пусто — координаты будут получены по адресу');
        yield NumberField::new('longitude', 'Долгота')
            ->setNumDecimals(6)
            ->setRequired(false)
            ->setHelp('Необязательно: если пусто — координаты будут получены по адресу');
        yield TextField::new('address', 'Адрес')
            ->setHelp('Используется в блоке «Информация» и для геокодинга координат, если широта/долгота не указаны')
            ->hideOnIndex();
        yield NumberField::new('radiusKm', 'Радиус (км)')
            ->setNumDecimals(2)
            ->setHelp('Объявления в этом радиусе попадут в каталог «возле»');

        $imageField = ImageField::new('imageUrl', 'Изображение')
            ->setBasePath('uploads/landmarks')
            ->setUploadDir('public/uploads/landmarks')
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setHelp('Загрузите файл, внешние URL не поддерживаются')
            ->hideOnIndex();

        if ($landmarkOnEdit instanceof Landmark && $landmarkOnEdit->getImageUrl()) {
            $imageUrl = $landmarkOnEdit->getImageUrl();
            $escapedImageUrl = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');

            $imageField
                ->setHelp(sprintf(
                    '<div style="margin-bottom:8px;">Текущее изображение:</div><a href="%1$s" target="_blank" rel="noopener noreferrer"><img src="%1$s" alt="Landmark image" style="max-width:360px;width:100%%;height:auto;border-radius:8px;border:1px solid #ddd;" /></a><div style="margin-top:8px;">Загрузите новый файл, чтобы заменить текущее изображение.</div>',
                    $escapedImageUrl
                ))
                ->setFormTypeOption('help_html', true);
        }

        yield $imageField;

        yield TextareaField::new('shortDescription', 'Краткое описание')
            ->setFormTypeOption('attr', ['rows' => 3])
            ->hideOnIndex();

        yield TextareaField::new('description', 'Описание (HTML)')
            ->setFormTypeOption('attr', [
                'class' => 'ea-static-page-content-rte form-control',
                'rows' => 16,
                'data-upload-scope' => 'static-pages',
            ])
            ->hideOnIndex();

        $factsTextField = TextareaField::new('factsText', 'Свойства')
            ->setFormTypeOption('mapped', false)
            ->onlyOnForms()
            ->setHelp('По одному на строку в формате «Год открытия = 2006»')
            ->setFormTypeOption('attr', ['rows' => 6]);

        if ($landmarkOnEdit instanceof Landmark) {
            $factsTextField->setFormTypeOption('data', self::formatFactsForForm($landmarkOnEdit->getFacts()));
        } else {
            $factsTextField->setFormTypeOption('data', '');
        }

        yield $factsTextField;

        $guestTipsTextField = TextareaField::new('guestTipsText', 'Советы гостям')
            ->setFormTypeOption('mapped', false)
            ->onlyOnForms()
            ->setHelp('По одному совету на строку')
            ->setFormTypeOption('attr', ['rows' => 6]);

        if ($landmarkOnEdit instanceof Landmark) {
            $guestTipsTextField->setFormTypeOption('data', self::formatGuestTipsForForm($landmarkOnEdit->getGuestTips()));
        } else {
            $guestTipsTextField->setFormTypeOption('data', '');
        }

        yield $guestTipsTextField;

        yield IntegerField::new('sortOrder', 'Порядок');
        yield BooleanField::new('isActive', 'Активна');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('cityId')
            ->add(BooleanFilter::new('isActive'));
    }

    private function restoreLandmarkImageIfClearedWithoutIntent(
        EntityManagerInterface $entityManager,
        Landmark $landmark,
        ?Request $request,
    ): void {
        if ($request === null) {
            return;
        }

        $landmarkForm = $request->request->all('Landmark') ?: [];
        $fileBag = $request->files->get('Landmark') ?? [];

        $deleteRequested = !empty($landmarkForm['imageUrl']['delete']);
        $hasNewUpload = isset($fileBag['imageUrl']['file'])
            && $fileBag['imageUrl']['file'] instanceof UploadedFile
            && $fileBag['imageUrl']['file']->isValid();

        if ($deleteRequested || $hasNewUpload) {
            return;
        }

        if ($this->readLandmarkImageStorage($landmark) !== null) {
            return;
        }

        $uow = $entityManager->getUnitOfWork();
        if (!$uow->isInIdentityMap($landmark)) {
            return;
        }

        $original = $uow->getOriginalEntityData($landmark);
        $previous = $original['imageUrl'] ?? $original['image_url'] ?? null;
        if (!is_string($previous) || $previous === '') {
            return;
        }

        $landmark->setImageUrl($previous);
    }

    private function readLandmarkImageStorage(Landmark $landmark): ?string
    {
        $value = $landmark->getImageUrl();
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $value;
    }

    private function syncLandmarkImageAfterAdminUpload(EntityManagerInterface $entityManager, Landmark $landmark): void
    {
        $imageUrl = $landmark->getImageUrl();
        if ($imageUrl === null || $imageUrl === '') {
            return;
        }

        $relative = $this->resolveLandmarkImageRelativePath($imageUrl);
        if ($relative === null) {
            return;
        }

        $newRelative = $this->fileUploader->processStoredLandmarkImage($relative);
        $newUrl = $newRelative !== null
            ? '/uploads/' . $newRelative
            : '/uploads/' . $relative;

        if ($landmark->getImageUrl() !== $newUrl) {
            $landmark->setImageUrl($newUrl);
            $entityManager->flush();
        }
    }

    private function resolveLandmarkImageRelativePath(string $imageUrl): ?string
    {
        if (str_contains($imageUrl, '/uploads/landmarks/')) {
            return 'landmarks/' . basename($imageUrl);
        }

        if (str_starts_with($imageUrl, 'landmarks/')) {
            return $imageUrl;
        }

        if (!str_contains($imageUrl, '/')) {
            return 'landmarks/' . $imageUrl;
        }

        return null;
    }

    private function applyLandmarkSlugFromFormOrName(Landmark $landmark, array $formData): void
    {
        $slugText = isset($formData['slugText']) ? trim((string) $formData['slugText']) : '';
        if ($slugText !== '') {
            $slug = $this->slugGenerator->generate($slugText);
        } else {
            $slug = $this->slugGenerator->generate($landmark->getName());
            if ($slug === '') {
                $slug = 'landmark-' . time();
            }
        }

        $unique = $this->slugGenerator->ensureUniqueByPredicate(
            $slug,
            function (string $candidate) use ($landmark): bool {
                $existing = $this->landmarkRepository->findAnyByCityIdAndSlug(
                    $landmark->getCityId(),
                    $candidate,
                );

                if ($existing === null) {
                    return false;
                }

                $idReflection = new \ReflectionProperty($landmark, 'id');
                if (!$idReflection->isInitialized($landmark)) {
                    return true;
                }

                return $existing->getId() !== $landmark->getId();
            },
        );
        $landmark->setSlug($unique);
    }

    private function normalizeLandmark(Landmark $landmark): void
    {
        $this->normalizeLandmarkCoordinates($landmark);
        $this->geocodeLandmarkAddressIfNeeded($landmark);

        $raw = $landmark->getDescription();
        if ($raw === null || trim($raw) === '') {
            $landmark->setDescription(null);
        } else {
            $htmlNormalized = $this->articleHtmlNormalizer->normalize($raw);
            $sanitized = $this->articleTextSanitizer->sanitizeHtml($htmlNormalized);
            $landmark->setDescription(trim($sanitized) === '' ? null : $sanitized);
        }

        $short = $landmark->getShortDescription();
        if ($short !== null && trim($short) === '') {
            $landmark->setShortDescription(null);
        }

        $nameGenitive = trim($landmark->getNameGenitive());
        if ($nameGenitive === '') {
            throw new \InvalidArgumentException('Укажите название в родительном падеже.');
        }
        $landmark->setNameGenitive($nameGenitive);

        $imageUrl = $landmark->getImageUrl();
        if ($imageUrl !== null && trim($imageUrl) === '') {
            $landmark->setImageUrl(null);
        }
    }

    private function normalizeLandmarkCoordinates(Landmark $landmark): void
    {
        $latitude = $landmark->getLatitude();
        $longitude = $landmark->getLongitude();

        if ($latitude === 0.0 && $longitude === 0.0) {
            $landmark->setLatitude(null);
            $landmark->setLongitude(null);
        }
    }

    private function geocodeLandmarkAddressIfNeeded(Landmark $landmark): void
    {
        if ($landmark->hasCoordinates()) {
            return;
        }

        $address = $landmark->getAddress();
        if ($address === null) {
            return;
        }

        $coordinates = $this->forwardGeocoder->geocodeAddress($address);
        if ($coordinates === null) {
            return;
        }

        $landmark->setLatitude($coordinates->getLatitude());
        $landmark->setLongitude($coordinates->getLongitude());
    }

    private function applyLandmarkFactsFromForm(Landmark $landmark, array $formData): void
    {
        $raw = isset($formData['factsText']) ? trim((string) $formData['factsText']) : '';
        if ($raw === '') {
            $landmark->setFacts(null);

            return;
        }

        $facts = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '' || !preg_match('/^(.+?)\s*=\s*(.+)$/u', $line, $matches)) {
                continue;
            }

            $label = trim($matches[1]);
            $value = trim($matches[2]);
            if ($label === '' || $value === '') {
                continue;
            }

            $facts[] = ['label' => $label, 'value' => $value];
        }

        $landmark->setFacts($facts === [] ? null : $facts);
    }

    private function applyLandmarkGuestTipsFromForm(Landmark $landmark, array $formData): void
    {
        $raw = isset($formData['guestTipsText']) ? trim((string) $formData['guestTipsText']) : '';
        if ($raw === '') {
            $landmark->setGuestTips(null);

            return;
        }

        $tips = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $tips[] = $line;
        }

        $landmark->setGuestTips($tips === [] ? null : $tips);
    }

    /**
     * @param list<array{label: string, value: string}>|null $facts
     */
    private static function formatFactsForForm(?array $facts): string
    {
        if ($facts === null || $facts === []) {
            return '';
        }

        $lines = [];
        foreach ($facts as $fact) {
            if (!is_array($fact)) {
                continue;
            }

            $label = trim((string) ($fact['label'] ?? ''));
            $value = trim((string) ($fact['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $lines[] = $label . ' = ' . $value;
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<string>|null $guestTips
     */
    private static function formatGuestTipsForForm(?array $guestTips): string
    {
        if ($guestTips === null || $guestTips === []) {
            return '';
        }

        $lines = [];
        foreach ($guestTips as $tip) {
            $line = trim((string) $tip);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }
}
