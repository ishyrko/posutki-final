<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Infrastructure\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminImageUploadController extends AbstractController
{
    public function __construct(
        private readonly FileUploader $fileUploader,
    ) {
    }

    #[Route('/admin/upload/image', name: 'admin_upload_image', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'Файл не загружен'], Response::HTTP_BAD_REQUEST);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            return $this->json(
                ['error' => 'Недопустимый тип файла. Допустимы: jpeg, png, webp'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $maxBytes = 20 * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            return $this->json(['error' => 'Файл слишком большой. Максимум 20 МБ'], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $scope = (string) $request->request->get('scope', 'static-pages');
        if (!in_array($scope, ['articles', 'static-pages'], true)) {
            return $this->json(['error' => 'Недопустимая область загрузки'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $relativePath = $this->fileUploader->upload($file, $scope);
            $url = '/uploads/' . ltrim($relativePath, '/');

            return $this->json(['location' => $url]);
        } catch (\Throwable $e) {
            return $this->json(
                ['error' => 'Ошибка загрузки файла: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
