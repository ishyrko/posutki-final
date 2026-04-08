<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Infrastructure\Service\FileUploader;
use App\Presentation\Api\Response\ApiResponse;
use App\Domain\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_')]
class UploadController extends AbstractController
{
    public function __construct(
        private readonly FileUploader $fileUploader,
    ) {
    }

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(
        Request $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(
                ApiResponse::error('Файл не загружен', 400),
                400
            );
        }

        // Validate file type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(
                ApiResponse::error('Недопустимый тип файла. Допустимы: jpeg, png, webp', 400),
                400
            );
        }

        // Validate file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->json(
                ApiResponse::error('Файл слишком большой. Максимум 10 МБ', 413),
                413
            );
        }

        try {
            $scope = (string) $request->request->get('scope', 'properties');
            if ($scope === 'articles' && !$user) {
                return $this->json(
                    ApiResponse::error('Требуется авторизация', 401),
                    401
                );
            }

            $relativePath = $this->fileUploader->upload($file, $scope);
            $url = '/uploads/' . ltrim($relativePath, '/');
            $thumbnailUrl = $this->fileUploader->buildThumbnailPublicUrl($url);

            return $this->json(ApiResponse::success([
                'url' => $url,
                'thumbnailUrl' => $thumbnailUrl,
            ]));
        } catch (\InvalidArgumentException $e) {
            return $this->json(
                ApiResponse::error('Недопустимая область загрузки. Допустимы: properties, articles', 400),
                400
            );
        } catch (\Exception $e) {
            return $this->json(
                ApiResponse::error('Ошибка загрузки файла: ' . $e->getMessage(), 500),
                500
            );
        }
    }

}