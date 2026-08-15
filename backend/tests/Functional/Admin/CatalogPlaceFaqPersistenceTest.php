<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityMicrodistrict;
use App\Presentation\Admin\Form\CatalogFaqItemType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;

final class CatalogPlaceFaqPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private FormFactoryInterface $formFactory;
    private CatalogPlaceContentNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->formFactory = static::getContainer()->get('form.factory');
        $this->normalizer = new CatalogPlaceContentNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testRemovingFirstFaqItemPersistsAfterFlush(): void
    {
        $micro = $this->createMicrodistrictWithFaq([
            ['question' => 'Question 0', 'answer' => 'Answer 0'],
            ['question' => 'Question 1', 'answer' => 'Answer 1'],
            ['question' => 'Question 2', 'answer' => 'Answer 2'],
        ]);

        $micro->setCatalogFaq(array_slice($micro->getCatalogFaq() ?? [], 1, null, true));
        $this->normalizer->normalizeEntity($micro);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(CityMicrodistrict::class, $micro->getId());
        self::assertNotNull($reloaded);
        self::assertCount(2, $reloaded->getCatalogFaq() ?? []);
        self::assertSame('Question 1', $reloaded->getCatalogFaq()[0]['question']);
    }

    public function testCollectionFormSubmitWithoutFirstItemRemovesItWhenByReferenceFalse(): void
    {
        $micro = $this->createMicrodistrictWithFaq([
            ['question' => 'Question 0', 'answer' => 'Answer 0'],
            ['question' => 'Question 1', 'answer' => 'Answer 1'],
        ]);

        $this->submitFaqFormWithoutFirstItem($micro, false);

        $this->normalizer->normalizeEntity($micro);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(CityMicrodistrict::class, $micro->getId());
        self::assertNotNull($reloaded);
        self::assertCount(1, $reloaded->getCatalogFaq() ?? []);
        self::assertSame('Question 1', $reloaded->getCatalogFaq()[0]['question']);
    }

    public function testCollectionFormSubmitWithoutFirstItemRemovesItWhenByReferenceTrue(): void
    {
        $micro = $this->createMicrodistrictWithFaq([
            ['question' => 'Question 0', 'answer' => 'Answer 0'],
            ['question' => 'Question 1', 'answer' => 'Answer 1'],
        ]);

        $this->submitFaqFormWithoutFirstItem($micro, true);
        self::assertCount(1, $micro->getCatalogFaq() ?? []);

        $this->normalizer->normalizeEntity($micro);

        $metadata = $this->entityManager->getClassMetadata(CityMicrodistrict::class);
        $this->entityManager->getUnitOfWork()->computeChangeSet($metadata, $micro);
        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($micro);

        self::assertArrayHasKey(
            'catalogFaq',
            $changeSet,
            'Doctrine must detect catalogFaq changes even when the collection form uses by_reference=true',
        );

        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloaded = $this->entityManager->find(CityMicrodistrict::class, $micro->getId());
        self::assertNotNull($reloaded);
        self::assertCount(1, $reloaded->getCatalogFaq() ?? []);
    }

    private function submitFaqFormWithoutFirstItem(CityMicrodistrict $micro, bool $byReference): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, $micro, ['csrf_protection' => false])
            ->add('catalogFaq', CollectionType::class, [
                'entry_type' => CatalogFaqItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => $byReference,
                'delete_empty' => true,
            ])
            ->getForm();

        $form->submit([
            'catalogFaq' => [
                1 => ['question' => 'Question 1', 'answer' => 'Answer 1'],
            ],
        ]);

        if (!$form->isValid()) {
            $messages = [];
            foreach ($form->getErrors(true) as $error) {
                $messages[] = $error->getMessage();
            }
            self::fail('Form is invalid: ' . implode('; ', $messages));
        }
    }

    /**
     * @param list<array{question: string, answer: string}> $faq
     */
    private function createMicrodistrictWithFaq(array $faq): CityMicrodistrict
    {
        $city = new City();
        $this->setPrivate($city, 'name', 'Test City');
        $this->setPrivate($city, 'slug', 'test-city');
        $this->setPrivate($city, 'shortName', 'г. Test');
        $this->setPrivate($city, 'ruralCouncil', null);
        $this->setPrivate($city, 'latitude', '53.9000000');
        $this->setPrivate($city, 'longitude', '27.5667000');
        $this->setPrivate($city, 'externalId', null);
        $this->setPrivate($city, 'isMain', true);
        $this->setPrivate($city, 'regionDistrict', null);

        $this->entityManager->persist($city);
        $this->entityManager->flush();

        $micro = new CityMicrodistrict(
            $city->getId(),
            'микрорайон Test',
            'Test',
            'Test',
            'test',
        );
        $micro->setCatalogFaq($faq);
        $this->normalizer->normalizeEntity($micro);

        $this->entityManager->persist($micro);
        $this->entityManager->flush();

        return $micro;
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
