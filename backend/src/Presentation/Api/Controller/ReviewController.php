<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Review\Entity\Review;
use App\Domain\Review\Repository\ReviewRepositoryInterface;
use App\Domain\Review\ValueObject\ReviewStatus;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {
    }

    #[Route('/properties/{propertyId}/reviews', name: 'api_property_reviews_list', methods: ['GET'], requirements: ['propertyId' => '\d+'])]
    public function listByProperty(string $propertyId): JsonResponse
    {
        $pid = Id::fromString($propertyId);
        $property = $this->propertyRepository->findById($pid);
        if ($property === null) {
            return $this->json(ApiResponse::error('Объявление не найдено', 404), 404);
        }

        $aggregate = $this->reviewRepository->getAggregateByPropertyId($pid);
        $reviews = $this->reviewRepository->findApprovedByPropertyId($pid);

        $items = array_map(static function (Review $r): array {
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
            ];
        }, $reviews);

        return $this->json(ApiResponse::success([
            'items' => $items,
            'ratingAvg' => $aggregate['avg'],
            'reviewCount' => $aggregate['count'],
        ]));
    }

    #[Route('/properties/{propertyId}/reviews', name: 'api_property_reviews_create', methods: ['POST'], requirements: ['propertyId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function create(string $propertyId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
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
        if ($text !== null && !is_string($text)) {
            return $this->json(ApiResponse::error('Поле text должно быть строкой', 400), 400);
        }
        $text = is_string($text) ? trim($text) : null;
        if ($text === '') {
            $text = null;
        }

        $existing = $this->reviewRepository->findByAuthorAndProperty($user->getId(), $pid);
        if ($existing !== null) {
            if ($existing->getStatus() === ReviewStatus::Pending) {
                return $this->json(ApiResponse::error('Отзыв уже отправлен на модерацию', 409), 409);
            }
            if ($existing->getStatus() === ReviewStatus::Approved) {
                return $this->json(ApiResponse::error('Вы уже оставили отзыв', 409), 409);
            }
            $existing->resubmitToPending($rating, $text);
            $this->reviewRepository->save($existing);

            return $this->json(ApiResponse::success([
                'id' => $existing->getId()?->getValue(),
                'status' => $existing->getStatus()->value,
                'message' => 'Отзыв снова отправлен на модерацию',
            ]), Response::HTTP_CREATED);
        }

        $review = new Review($property, $user, $rating, $text);
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

        if ($review->getStatus() !== ReviewStatus::Pending) {
            return $this->json(ApiResponse::error('Удалить можно только отзыв, ожидающий модерации', 400), 400);
        }

        $this->reviewRepository->delete($review);

        return $this->json(ApiResponse::success(['message' => 'Отзыв удалён']));
    }
}
