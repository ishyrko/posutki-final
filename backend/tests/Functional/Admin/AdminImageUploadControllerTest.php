<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminImageUploadControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->resetDatabase();
    }

    public function testUploadWithoutAdminSessionIsDenied(): void
    {
        $this->client->request(
            'POST',
            '/admin/upload/image',
            files: ['file' => $this->createTestImage()],
        );

        self::assertResponseStatusCodeSame(302);
        self::assertStringContainsString('/admin/login', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testUploadWithAdminSessionReturnsOptimizedImageLocation(): void
    {
        $email = 'admin-upload@example.com';
        $password = 'Password123!';
        $this->createAdminUser($email, $password);
        $this->loginAsAdmin($email, $password);

        $this->client->request(
            'POST',
            '/admin/upload/image',
            ['scope' => 'static-pages'],
            ['file' => $this->createTestImage()],
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertArrayHasKey('location', $payload);
        self::assertMatchesRegularExpression('#^/uploads/static-pages/.+\.(webp|jpg)$#', $payload['location']);

        $uploadRoot = static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads';
        $relativePath = ltrim(substr($payload['location'], strlen('/uploads/')), '/');
        self::assertFileExists($uploadRoot . '/' . $relativePath);
    }

    public function testUploadWithArticlesScopeStoresUnderArticlesDirectory(): void
    {
        $email = 'admin-article-upload@example.com';
        $password = 'Password123!';
        $this->createAdminUser($email, $password);
        $this->loginAsAdmin($email, $password);

        $this->client->request(
            'POST',
            '/admin/upload/image',
            ['scope' => 'articles'],
            ['file' => $this->createTestImage()],
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertMatchesRegularExpression('#^/uploads/articles/.+\.(webp|jpg)$#', $payload['location']);
    }

    public function testUploadWithInvalidScopeIsRejected(): void
    {
        $email = 'admin-invalid-scope@example.com';
        $password = 'Password123!';
        $this->createAdminUser($email, $password);
        $this->loginAsAdmin($email, $password);

        $this->client->request(
            'POST',
            '/admin/upload/image',
            ['scope' => 'properties'],
            ['file' => $this->createTestImage()],
        );

        self::assertResponseStatusCodeSame(400);
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

        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

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

    private function createTestImage(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'admin-upload-test-');
        self::assertNotFalse($path);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        self::assertNotFalse($png);
        file_put_contents($path, $png);

        return new UploadedFile($path, 'test.png', 'image/png', null, true);
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        return $entityManager;
    }

    private function resetDatabase(): void
    {
        $entityManager = $this->entityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
}
