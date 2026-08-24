<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\PropertyRevision;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Infrastructure\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminPropertyImageController extends AbstractController
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyRevisionRepositoryInterface $revisionRepository,
        private readonly FileUploader $fileUploader,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/admin/properties/{id}/images/rotate', name: 'admin_property_image_rotate', methods: ['POST'])]
    public function rotate(int $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $csrfToken = $request->headers->get('X-CSRF-TOKEN')
            ?? (is_array($payload) ? ($payload['_token'] ?? null) : null);
        if (
            !is_string($csrfToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken('admin_property_image_rotate', $csrfToken))
        ) {
            return $this->json(['error' => 'Неверный CSRF-токен'], Response::HTTP_FORBIDDEN);
        }

        $property = $this->propertyRepository->findById(Id::fromInt($id));
        if ($property === null) {
            return $this->json(['error' => 'Объявление не найдено'], Response::HTTP_NOT_FOUND);
        }

        $url = is_array($payload) && isset($payload['url']) ? trim((string) $payload['url']) : '';
        if ($url === '' || !$this->fileUploader->isLocalPropertyImageUrl($url)) {
            return $this->json(['error' => 'Недопустимый URL изображения'], Response::HTTP_BAD_REQUEST);
        }

        $images = $property->getImages();
        $index = array_search($url, $images, true);
        if ($index === false) {
            return $this->json(['error' => 'Изображение не принадлежит объявлению'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->fileUploader->rotatePropertyImage($url);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json(
                ['error' => 'Ошибка поворота изображения: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        $images[$index] = $result['url'];
        $property->setImages(array_values($images));

        $revision = $this->revisionRepository->findLatestByPropertyAndStatus(
            $property->getId()->getValue(),
            PropertyRevision::STATUS_PENDING,
        );
        if ($revision !== null) {
            $revision->replaceImageUrls([$url => $result['url']]);
        }

        $this->entityManager->flush();

        return $this->json($result);
    }
}
