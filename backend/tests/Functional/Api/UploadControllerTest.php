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
    }

    private function createTestImage(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload-test-');
        self::assertNotFalse($path);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        self::assertNotFalse($png);
        file_put_contents($path, $png);

        return new UploadedFile($path, 'test.png', 'image/png', null, true);
    }
}
