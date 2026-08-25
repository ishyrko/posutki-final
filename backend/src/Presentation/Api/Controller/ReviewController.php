<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Review\Entity\Review;
use App\Domain\Review\Event\ReviewRepliedEvent;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Review\ValueObject\ReviewStatus;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly MessageBusInterface $notificationBus,
    ) {
    }

    #[Route('/properties/{propertyId}/reviews', name: 'api_property_reviews_list', methods: ['GET'], requirements: ['propertyId' => '\d+'])]
    public function listByProperty(string $propertyId, #[CurrentUser] ?User $user = null): JsonResponse
    {
        $pid = Id::fromString($propertyId);
        $property = $this->propertyRepository->findById($pid);
        if ($property === null) {
            return $this->json(ApiResponse::error('Объявление не найдено', 404), 404);
        }

        $aggregate = $this->reviewRepository->getAggregateByPropertyId($pid);
        $reviews = $this->reviewRepository->findApprovedByPropertyId($pid);

        $items = array_map(static fn (Review $r): array => self::serializePublicReview($r), $reviews);

        $viewerReview = null;
        if ($user !== null) {
            $existing = $this->reviewRepository->findActiveByAuthorAndProperty($user->getId(), $pid);
            if ($existing !== null && $existing->getId() !== null) {
                $viewerReview = [
                    'id' => $existing->getId()->getValue(),
                    'status' => $existing->getStatus()->value,
                ];
            }
        }

        return $this->json(ApiResponse::success([
            'items' => $items,
            'ratingAvg' => $aggregate['avg'],
            'reviewCount' => $aggregate['count'],
            'viewerReview' => $viewerReview,
        ]));
    }

    #[Route('/properties/{propertyId}/reviews', name: 'api_property_reviews_create', methods: ['POST'], requirements: ['propertyId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function create(string $propertyId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        if (!$user->isPhoneVerified()) {
            return $this->json(ApiResponse::error('Для отзыва необходимо подтвердить телефон в профиле', 403), 403);
        }

        $pid = Id::fromString($propertyId);
        $property = $this->propertyRepository->findById($pid);
        if ($property === null) {
            return $this->json(ApiResponse::error('Объявление не найдено', 404), 404);
        }

        if ($property->getStatus() !== 'published') {
            return $this->json(ApiResponse::error('Отзывы можно оставлять только к опубликованным объявлениям', 400), 400);
        }

        if ($property->isOwnedBy((string) $user->getId()->getValue())) {
            return $this->json(ApiResponse::error('Нельзя оставить отзыв на своё объявление', 403), 403);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(ApiResponse::error('Некорректный JSON', 400), 400);
        }

        $rating = $payload['rating'] ?? null;
        if (!is_int($rating) && !is_numeric($rating)) {
            return $this->json(ApiResponse::error('Укажите оценку от 1 до 5', 400), 400);
        }
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 5) {
            return $this->json(ApiResponse::error('Укажите оценку от 1 до 5', 400), 400);
        }

        $text = $payload['text'] ?? null;
        if (!is_string($text)) {
            return $this->json(ApiResponse::error('Укажите текст отзыва', 400), 400);
        }
        $text = trim($text);
        if ($text === '') {
            return $this->json(ApiResponse::error('Укажите текст отзыва', 400), 400);
        }

        $shareDataWithOwner = self::parseShareDataWithOwner($payload);

        $existing = $this->reviewRepository->findActiveByAuthorAndProperty($user->getId(), $pid);
        if ($existing !== null) {
            if ($existing->getStatus() === ReviewStatus::Pending) {
                return $this->json(ApiResponse::error('Отзыв уже отправлен на модерацию', 409), 409);
            }
            if ($existing->getStatus() === ReviewStatus::Approved) {
                return $this->json(ApiResponse::error('Вы уже оставили отзыв', 409), 409);
            }
            $existing->resubmitToPending($rating, $text, $shareDataWithOwner);
            $this->reviewRepository->save($existing);

            return $this->json(ApiResponse::success([
                'id' => $existing->getId()?->getValue(),
                'status' => $existing->getStatus()->value,
                'message' => 'Отзыв снова отправлен на модерацию',
            ]), Response::HTTP_CREATED);
        }

        $review = new Review($property, $user, $rating, $text, $shareDataWithOwner);
        $this->reviewRepository->save($review);

        return $this->json(ApiResponse::success([
            'id' => $review->getId()?->getValue(),
            'status' => $review->getStatus()->value,
            'message' => 'Отзыв отправлен на модерацию',
        ]), Response::HTTP_CREATED);
    }

    #[Route('/reviews/{id}', name: 'api_reviews_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $reviewId = Id::fromString($id);
        $review = $this->reviewRepository->findById($reviewId);
        if ($review === null) {
            return $this->json(ApiResponse::error('Отзыв не найден', 404), 404);
        }

        if (!$review->isOwnedBy($user->getId())) {
            return $this->json(ApiResponse::error('Нет прав на удаление', 403), 403);
        }

        if ($review->getStatus() === ReviewStatus::Deleted) {
            return $this->json(ApiResponse::error('Отзыв уже удалён', 400), 400);
        }

        $review->softDelete();
        $this->reviewRepository->save($review);

        return $this->json(ApiResponse::success(['message' => 'Отзыв удалён']));
    }

    #[Route('/me/reviews', name: 'api_me_reviews_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listForAuthor(#[CurrentUser] User $user): JsonResponse
    {
        $reviews = $this->reviewRepository->findActiveByAuthorId($user->getId());
        $items = array_map(fn (Review $r): array => $this->serializeAuthorReview($r), $reviews);

        return $this->json(ApiResponse::success(['items' => $items]));
    }

    #[Route('/owner/reviews', name: 'api_owner_reviews_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listForOwner(#[CurrentUser] User $user): JsonResponse
    {
        $reviews = $this->reviewRepository->findApprovedByOwnerId($user->getId());
        $items = array_map(fn (Review $r): array => $this->serializeOwnerReview($r), $reviews);

        return $this->json(ApiResponse::success(['items' => $items]));
    }

    #[Route('/owner/properties/{propertyId}/reviews', name: 'api_owner_property_reviews_list', methods: ['GET'], requirements: ['propertyId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function listForOwnerProperty(string $propertyId, #[CurrentUser] User $user): JsonResponse
    {
        $pid = Id::fromString($propertyId);
        $property = $this->propertyRepository->findById($pid);
        if ($property === null) {
            return $this->json(ApiResponse::error('Объявление не найдено', 404), 404);
        }

        if (!$property->isOwnedBy((string) $user->getId()->getValue())) {
            return $this->json(ApiResponse::error('Нет прав на просмотр отзывов', 403), 403);
        }

        $reviews = $this->reviewRepository->findApprovedByPropertyIdForOwner($pid, $user->getId());
        $this->reviewRepository->markSeenForPropertyOwner($pid, $user->getId());

        $items = array_map(fn (Review $r): array => $this->serializeOwnerReview($r), $reviews);

        return $this->json(ApiResponse::success([
            'items' => $items,
            'property' => [
                'id' => $property->getId()->getValue(),
                'title' => $property->getTitle(),
            ],
        ]));
    }

    #[Route('/reviews/{id}/reply', name: 'api_reviews_reply', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function reply(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $reviewId = Id::fromString($id);
        $review = $this->reviewRepository->findById($reviewId);
        if ($review === null) {
            return $this->json(ApiResponse::error('Отзыв не найден', 404), 404);
        }

        $property = $review->getProperty();
        if (!$property->isOwnedBy((string) $user->getId()->getValue())) {
            return $this->json(ApiResponse::error('Нет прав на ответ', 403), 403);
        }

        if ($review->getStatus() !== ReviewStatus::Approved) {
            return $this->json(ApiResponse::error('Ответить можно только на одобренный отзыв', 400), 400);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(ApiResponse::error('Некорректный JSON', 400), 400);
        }

        $text = $payload['text'] ?? null;
        if (!is_string($text)) {
            return $this->json(ApiResponse::error('Укажите текст ответа', 400), 400);
        }

        try {
            $review->reply($text);
        } catch (\InvalidArgumentException $e) {
            return $this->json(ApiResponse::error($e->getMessage(), 400), 400);
        }

        $this->reviewRepository->save($review);

        if ($review->getRating() >= 4 && $review->getId() !== null) {
            $this->notificationBus->dispatch(new ReviewRepliedEvent((string) $review->getId()->getValue()));
        }

        return $this->json(ApiResponse::success([
            'id' => $review->getId()?->getValue(),
            'ownerReply' => $review->getOwnerReply(),
            'ownerRepliedAt' => $review->getOwnerRepliedAt()?->format('c'),
            'message' => 'Ответ сохранён',
        ]));
    }

    /** @param array<string, mixed> $payload */
    private static function parseShareDataWithOwner(array $payload): bool
    {
        if (!array_key_exists('shareDataWithOwner', $payload)) {
            return true;
        }

        return filter_var($payload['shareDataWithOwner'], FILTER_VALIDATE_BOOLEAN);
    }

    private static function serializePublicReview(Review $r): array
    {
        $author = $r->getAuthor();

        return [
            'id' => $r->getId()?->getValue(),
            'rating' => $r->getRating(),
            'text' => $r->getText(),
            'author' => [
                'id' => $author->getId()->getValue(),
                'firstName' => $author->getFirstName(),
                'lastName' => $author->getLastName(),
            ],
            'createdAt' => $r->getCreatedAt()->format('c'),
            'ownerReply' => $r->getOwnerReply(),
            'ownerRepliedAt' => $r->getOwnerRepliedAt()?->format('c'),
        ];
    }

    private function serializeAuthorReview(Review $r): array
    {
        $property = $r->getProperty();
        $isApproved = $r->getStatus() === ReviewStatus::Approved;

        return [
            'id' => $r->getId()?->getValue(),
            'rating' => $r->getRating(),
            'text' => $r->getText(),
            'status' => $r->getStatus()->value,
            'moderationComment' => $r->getModerationComment(),
            'createdAt' => $r->getCreatedAt()->format('c'),
            'ownerReply' => $isApproved ? $r->getOwnerReply() : null,
            'ownerRepliedAt' => $isApproved ? $r->getOwnerRepliedAt()?->format('c') : null,
            'property' => [
                'id' => $property->getId()->getValue(),
                'title' => $property->getTitle(),
                'type' => $property->getType(),
            ],
        ];
    }

    private function serializeOwnerReview(Review $r): array
    {
        $author = $r->getAuthor();
        $property = $r->getProperty();

        $authorPayload = [
            'firstName' => $author->getFirstName(),
            'lastName' => $author->getLastName(),
        ];

        if ($r->isShareDataWithOwner()) {
            $authorPayload['phone'] = $author->getPhone();
            $authorPayload['email'] = $author->getEmail()?->getValue();
        }

        return [
            'id' => $r->getId()?->getValue(),
            'rating' => $r->getRating(),
            'text' => $r->getText(),
            'shareDataWithOwner' => $r->isShareDataWithOwner(),
            'createdAt' => $r->getCreatedAt()->format('c'),
            'ownerReply' => $r->getOwnerReply(),
            'ownerRepliedAt' => $r->getOwnerRepliedAt()?->format('c'),
            'author' => $authorPayload,
            'property' => [
                'id' => $property->getId()->getValue(),
                'title' => $property->getTitle(),
            ],
        ];
    }
}
