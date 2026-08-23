<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

final class UploadControllerTest extends ApiTestCase
{
    public function testAccessMapRequiresAuthenticationForUpload(): void
    {
        $accessMap = static::getContainer()->get('security.access_map');

        $request = Request::create('/api/upload', 'POST');
        [$attributes] = $accessMap->getPatterns($request);

        self::assertSame([AuthenticatedVoter::IS_AUTHENTICATED_FULLY], $attributes);
    }

    public function testUploadWithoutAuthorizationIsDenied(): void
    {
        $this->client->request(
            'POST',
            '/api/upload',
            ['scope' => 'properties'],
            ['file' => $this->createTestImage()],
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testUploadWithInvalidJwtIsUnauthorized(): void
    {
        $this->client->request(
            'POST',
            '/api/upload',
            ['scope' => 'properties'],
            ['file' => $this->createTestImage()],
            server: ['HTTP_AUTHORIZATION' => 'Bearer eyJhbGciOiJSUzI1NiJ9.invalid'],
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUploadWithValidJwtSucceeds(): void
    {
        $email = 'upload-auth@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for upload test.');
        }

        $this->client->request(
            'POST',
            '/api/upload',
            ['scope' => 'properties'],
            ['file' => $this->createTestImage()],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($payload);
        $url = $payload['data']['url'] ?? null;
        self::assertIsString($url);
        self::assertMatchesRegularExpression('#^/uploads/properties/.+\.(webp|jpg)$#', $url);

        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $baseName = pathinfo($url, PATHINFO_FILENAME);
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        self::assertFileExists($projectDir . '/var/property-originals/' . $baseName . '.' . $extension);
        self::assertFileExists($projectDir . '/public/uploads/properties/' . $baseName . '.' . $extension);
    }

    public function testRotatePropertyImageWithValidJwtSucceeds(): void
    {
        $email = 'upload-rotate@example.com';
        $password = 'Password123!';
        $this->createUser($email, $password);

        $token = $this->loginAndGetToken($email, $password);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token for upload rotate test.');
        }

        $this->client->request(
            'POST',
            '/api/upload',
            ['scope' => 'properties'],
            ['file' => $this->createTestImage(width: 40, height: 20)],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        self::assertResponseIsSuccessful();

        $uploadPayload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $url = $uploadPayload['data']['url'] ?? null;
        self::assertIsString($url);

        $this->client->request(
            'POST',
            '/api/upload/rotate',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['url' => $url], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $rotatePayload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($rotatePayload);
        self::assertNotSame($url, $rotatePayload['data']['url'] ?? null);
        self::assertMatchesRegularExpression('#^/uploads/properties/thumbs/.+\.(webp|jpg)$#', $rotatePayload['data']['thumbnailUrl'] ?? '');
    }

    private function createTestImage(int $width = 1, int $height = 1): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload-test-');
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
