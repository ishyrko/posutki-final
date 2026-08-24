<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use App\Tests\Functional\Api\ApiTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminPropertyImageRotateTest extends ApiTestCase
{
    public function testRotateWithoutAdminSessionIsDenied(): void
    {
        $this->client->request(
            'POST',
            '/admin/properties/1/images/rotate',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['url' => '/uploads/properties/test.webp'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(302);
        self::assertStringContainsString('/admin/login', (string) $this->client->getResponse()->headers->get('Location'));
    }

    public function testRotateWithAdminSessionUpdatesPropertyImages(): void
    {
        $owner = $this->createUser('owner-rotate@example.com');
        $city = $this->createCity();
        $property = $this->createProperty($owner, $city, 'published');

        $token = $this->loginAndGetToken('owner-rotate@example.com', 'Password123!');
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for property image upload.');
        }

        $this->client->request(
            'POST',
            '/api/upload',
            ['scope' => 'properties'],
            ['file' => $this->createTestImage(width: 40, height: 20)],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        self::assertResponseIsSuccessful();

        $uploadPayload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $url = $uploadPayload['data']['url'] ?? null;
        self::assertIsString($url);

        $property = $this->reloadProperty($property);
        $property->setImages([$url]);
        $this->entityManager()->flush();

        $this->createAdminUser('admin-rotate@example.com', 'Password123!');
        $admin = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => Email::fromString('admin-rotate@example.com')]);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $csrfToken = $this->csrfToken($property->getId()->getValue());

        $this->client->request(
            'POST',
            sprintf('/admin/properties/%d/images/rotate', $property->getId()->getValue()),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CSRF_TOKEN' => $csrfToken,
            ],
            content: json_encode(['url' => $url], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();

        $rotatePayload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($rotatePayload);
        self::assertArrayHasKey('url', $rotatePayload);
        self::assertNotSame($url, $rotatePayload['url']);
        self::assertMatchesRegularExpression('#^/uploads/properties/thumbs/.+\.(webp|jpg)$#', $rotatePayload['thumbnailUrl'] ?? '');

        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Property::class, $property->getId()->getValue());
        self::assertInstanceOf(Property::class, $reloaded);
        self::assertSame([$rotatePayload['url']], $reloaded->getImages());
    }

    public function testRotateUpdatesPendingRevisionImages(): void
    {
        $owner = $this->createUser('owner-revision-rotate@example.com');
        $city = $this->createCity(slug: 'minsk-revision-rotate');
        $property = $this->createProperty($owner, $city, 'published');

        $token = $this->loginAndGetToken('owner-revision-rotate@example.com', 'Password123!');
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for property image upload.');
        }

        $this->client->request(
            'POST',
            '/api/upload',
            ['scope' => 'properties'],
            ['file' => $this->createTestImage(width: 30, height: 20)],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        self::assertResponseIsSuccessful();

        $uploadPayload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $url = $uploadPayload['data']['url'] ?? null;
        self::assertIsString($url);

        $property = $this->reloadProperty($property);
        $property->setImages([$url]);
        $revision = new PropertyRevision($property, ['images' => [$url], 'title' => $property->getTitle()]);
        $this->entityManager()->persist($revision);
        $this->entityManager()->flush();
        $revisionId = $revision->getId()->getValue();

        $this->createAdminUser('admin-revision-rotate@example.com', 'Password123!');
        $admin = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => Email::fromString('admin-revision-rotate@example.com')]);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->client->request(
            'POST',
            sprintf('/admin/properties/%d/images/rotate', $property->getId()->getValue()),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CSRF_TOKEN' => $this->csrfToken($property->getId()->getValue()),
            ],
            content: json_encode(['url' => $url], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();

        $rotatePayload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsString($rotatePayload['url'] ?? null);

        $this->entityManager()->clear();
        $reloadedRevision = $this->entityManager()
            ->getRepository(PropertyRevision::class)
            ->find($revisionId);
        self::assertInstanceOf(PropertyRevision::class, $reloadedRevision);
        self::assertSame([$rotatePayload['url']], $reloadedRevision->getData()['images']);
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

    private function csrfToken(int $propertyId): string
    {
        $crawler = $this->client->request('GET', sprintf('/admin/property/%d/edit', $propertyId));
        self::assertResponseIsSuccessful();

        $previewToken = $crawler->filter('[data-csrf-token]');
        if ($previewToken->count() > 0) {
            return (string) $previewToken->attr('data-csrf-token');
        }

        $formToken = $crawler->filter('input[name="_csrf_token"]');
        self::assertGreaterThan(0, $formToken->count(), 'CSRF token input not found on admin property edit form');

        return (string) $formToken->attr('value');
    }

    private function reloadProperty(Property $property): Property
    {
        $reloaded = $this->entityManager()->find(Property::class, $property->getId()->getValue());
        self::assertInstanceOf(Property::class, $reloaded);

        return $reloaded;
    }

    private function createTestImage(int $width = 1, int $height = 1): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'admin-rotate-test-');
        self::assertNotFalse($path);

        if ($width === 1 && $height === 1) {
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
            self::assertNotFalse($png);
            file_put_contents($path, $png);
        } else {
            $image = imagecreatetruecolor($width, $height);
            $color = imagecolorallocate($image, 200, 100, 50);
            imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);
            imagepng($image, $path);
            imagedestroy($image);
        }

        return new UploadedFile($path, 'test.png', 'image/png', null, true);
    }
}
