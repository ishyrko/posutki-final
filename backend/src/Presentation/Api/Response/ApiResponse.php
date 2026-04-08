<?php

declare(strict_types=1);

namespace App\Presentation\Api\Response;

class ApiResponse
{
    public static function success(mixed $data, int $status = 200): array
    {
        return [
            'success' => true,
            'data' => $data,
        ];
    }

    public static function error(string $message, int $code = 400, ?array $details = null): array
    {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ];

        if ($details) {
            $response['error']['details'] = $details;
        }

        return $response;
    }

    public static function validationError(array $violations): array
    {
        return [
            'success' => false,
            'error' => [
                'message' => 'Ошибка валидации',
                'code' => 422,
                'violations' => $violations,
            ],
        ];
    }

    public static function paginated(array $items, int $total, int $page, int $limit): array
    {
        return [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int)ceil($total / $limit),
            ],
        ];
    }
}