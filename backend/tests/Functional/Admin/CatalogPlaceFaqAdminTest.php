<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CatalogPlaceFaqAdminTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private CatalogPlaceContentNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->normalizer = new CatalogPlaceContentNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );
        $this->resetDatabase();
    }

    public function testAdminEditRemovesFirstFaqItem(): void
    {
        $this->createAdminUser('admin-faq@example.com', 'Password123!');
        $this->loginAsAdmin('admin-faq@example.com', 'Password123!');

        $micro = $this->createMicrodistrictWithFaq([
            ['question' => 'Question 0', 'answer' => 'Answer 0'],
            ['question' => 'Question 1', 'answer' => 'Answer 1'],
            ['question' => 'Question 2', 'answer' => 'Answer 2'],
        ]);

        $editUrl = sprintf('/admin/city-microdistrict/%d/edit', $micro->getId());

        $crawler = $this->client->request('GET', $editUrl);
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form();
        $formData = $form->getPhpValues();

        self::assertSame('post', strtolower($form->getMethod()), 'Expected standard POST submit for admin edit form');

        self::assertArrayHasKey('CityMicrodistrict', $formData);
        self::assertArrayHasKey('catalogFaq', $formData['CityMicrodistrict']);

        unset($formData['CityMicrodistrict']['catalogFaq'][0]);

        $this->client->request($form->getMethod(), $form->getUri(), $formData);
        self::assertResponseRedirects();

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(CityMicrodistrict::class, $micro->getId());
        self::assertNotNull($reloaded);

        $faq = $reloaded->getCatalogFaq();
        self::assertIsArray($faq);
        self::assertCount(2, $faq, 'Expected first FAQ item to be removed after admin save');
        self::assertSame('Question 1', $faq[0]['question']);
        self::assertSame('Question 2', $faq[1]['question']);
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

    private function createAdminUser(string $email, string $plainPassword): User
    {
        $user = User::register(
            Email::fromString($email),
            '',
            'Admin',
            'User',
        );

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $hasher->hashPassword($user, $plainPassword);

        $passwordReflection = new \ReflectionProperty($user, 'password');
        $passwordReflection->setAccessible(true);
        $passwordReflection->setValue($user, $hashedPassword);

        $user->verify();
        $user->grantRole('ROLE_ADMIN');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function loginAsAdmin(string $email, string $password): void
    {
        $crawler = $this->client->request('GET', '/admin/login');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Войти')->form([
            '_username' => $email,
            '_password' => $password,
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects('/admin');
        $this->client->followRedirect();
    }

    private function resetDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
