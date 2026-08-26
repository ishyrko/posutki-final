<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Review\Entity\Review;
use App\Domain\Review\ValueObject\ReviewStatus;
use App\Domain\User\Entity\User;

final class ReviewControllerTest extends ApiTestCase
{
    public function testCreateReviewRequiresVerifiedPhone(): void
    {
        $owner = $this->createUser('owner-review-phone@example.com', 'Password123!');
        $authorEmail = 'author-no-phone@example.com';
        $authorPassword = 'Password123!';
        $author = $this->createUser($authorEmail, $authorPassword);
        $city = $this->createCity('Minsk Review Phone', 'minsk-review-phone', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');

        $token = $this->loginAndGetToken($authorEmail, $authorPassword);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'POST',
            '/api/properties/' . $property->getId()->getValue() . '/reviews',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['rating' => 5, 'text' => 'Отлично!'], JSON_THROW_ON_ERROR),
        );

        self::assertSame(403, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateReviewDefaultsShareDataWithOwnerToTrue(): void
    {
        [$property, $author, $token] = $this->createReviewScenario();
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'POST',
            '/api/properties/' . $property->getId()->getValue() . '/reviews',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['rating' => 5, 'text' => 'Отлично!'], JSON_THROW_ON_ERROR),
        );

        self::assertSame(201, $this->client->getResponse()->getStatusCode());

        $review = $this->entityManager()->getRepository(Review::class)->findOneBy(['property' => $property, 'author' => $author]);
        self::assertInstanceOf(Review::class, $review);
        self::assertTrue($review->isShareDataWithOwner());
    }

    public function testSoftDeleteApprovedReviewAllowsNewReview(): void
    {
        [$property, $author, $token] = $this->createReviewScenario(true);
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $review = new Review($property, $author, 5, 'Первый отзыв', true);
        $review->approve();
        $this->entityManager()->persist($review);
        $this->entityManager()->flush();
        $reviewId = $review->getId()?->getValue();
        self::assertNotNull($reviewId);

        $this->client->request(
            'DELETE',
            '/api/reviews/' . $reviewId,
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->entityManager()->clear();
        $deleted = $this->entityManager()->find(Review::class, $reviewId);
        self::assertInstanceOf(Review::class, $deleted);
        self::assertSame(ReviewStatus::Deleted, $deleted->getStatus());

        $this->client->request(
            'POST',
            '/api/properties/' . $property->getId()->getValue() . '/reviews',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['rating' => 4, 'text' => 'Новый отзыв', 'shareDataWithOwner' => false], JSON_THROW_ON_ERROR),
        );
        self::assertSame(201, $this->client->getResponse()->getStatusCode());
    }

    public function testOwnerReplyAndPublicListIncludesReply(): void
    {
        $ownerEmail = 'owner-reply@example.com';
        $ownerPassword = 'Password123!';
        $owner = $this->createUser($ownerEmail, $ownerPassword);
        $authorEmail = 'author-reply@example.com';
        $authorPassword = 'Password123!';
        $author = $this->createUser($authorEmail, $authorPassword);
        $this->markPhoneVerified($author);
        $city = $this->createCity('Minsk Reply', 'minsk-reply', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');

        $review = new Review($property, $author, 5, 'Супер!', true);
        $review->approve();
        $this->entityManager()->persist($review);
        $this->entityManager()->flush();
        $reviewId = $review->getId()?->getValue();
        self::assertNotNull($reviewId);

        $ownerToken = $this->loginAndGetToken($ownerEmail, $ownerPassword);
        if ($ownerToken === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'POST',
            '/api/reviews/' . $reviewId . '/reply',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $ownerToken, 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['text' => 'Спасибо за отзыв!'], JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/properties/' . $property->getId()->getValue() . '/reviews');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Спасибо за отзыв!', $payload['data']['items'][0]['ownerReply']);
    }

    public function testPublicListIncludesViewerReviewForAuthor(): void
    {
        [$property, $author, $token] = $this->createReviewScenario();
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $review = new Review($property, $author, 5, 'Жду модерации', true);
        $this->entityManager()->persist($review);
        $this->entityManager()->flush();

        $this->client->request(
            'GET',
            '/api/properties/' . $property->getId()->getValue() . '/reviews',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('pending', $payload['data']['viewerReview']['status']);
        self::assertSame($review->getId()?->getValue(), $payload['data']['viewerReview']['id']);
    }

    public function testOwnerListMarksReviewsSeen(): void
    {
        $ownerEmail = 'owner-seen@example.com';
        $ownerPassword = 'Password123!';
        $owner = $this->createUser($ownerEmail, $ownerPassword);
        $author = $this->createUser('author-seen@example.com', 'Password123!');
        $city = $this->createCity('Minsk Seen', 'minsk-seen', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');

        $review = new Review($property, $author, 4, 'Норм', true);
        $review->approve();
        $this->entityManager()->persist($review);
        $this->entityManager()->flush();

        $ownerToken = $this->loginAndGetToken($ownerEmail, $ownerPassword);
        if ($ownerToken === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $this->client->request(
            'GET',
            '/api/owner/properties/' . $property->getId()->getValue() . '/reviews',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $ownerToken],
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->entityManager()->clear();
        $fresh = $this->entityManager()->getRepository(Review::class)->find($review->getId()?->getValue());
        self::assertInstanceOf(Review::class, $fresh);
        self::assertNotNull($fresh->getOwnerSeenAt());
    }

    public function testAuthorListsOwnReviewsExcludingDeleted(): void
    {
        [$property, $author, $token] = $this->createReviewScenario();
        if ($token === '') {
            self::markTestSkipped('Could not obtain JWT token.');
        }

        $pending = new Review($property, $author, 5, 'Жду модерации', true);
        $this->entityManager()->persist($pending);
        $this->entityManager()->flush();

        $approved = new Review($property, $author, 4, 'Уже был', true);
        $approved->softDelete();
        $this->entityManager()->persist($approved);
        $this->entityManager()->flush();

        $this->client->request(
            'GET',
            '/api/me/reviews',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $payload['data']['items']);
        self::assertSame('pending', $payload['data']['items'][0]['status']);
        self::assertSame('Жду модерации', $payload['data']['items'][0]['text']);
        self::assertSame($property->getId()->getValue(), $payload['data']['items'][0]['property']['id']);
        self::assertArrayHasKey('citySlug', $payload['data']['items'][0]['property']);
    }

    /** @return array{0: \App\Domain\Property\Entity\Property, 1: User, 2: string} */
    private function createReviewScenario(bool $verifyAuthorPhone = true): array
    {
        $owner = $this->createUser('owner-review@example.com', 'Password123!');
        $authorEmail = 'author-review@example.com';
        $authorPassword = 'Password123!';
        $author = $this->createUser($authorEmail, $authorPassword);
        if ($verifyAuthorPhone) {
            $this->markPhoneVerified($author);
        }
        $city = $this->createCity('Minsk Review', 'minsk-review', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');
        $token = $this->loginAndGetToken($authorEmail, $authorPassword);

        return [$property, $author, $token];
    }

    private function markPhoneVerified(User $user): void
    {
        $user->setVerifiedProfilePhone('+375291234567');
        $this->entityManager()->flush();
    }
}
