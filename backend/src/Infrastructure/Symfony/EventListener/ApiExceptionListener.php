<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventListener;

use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\Exception\NotFoundException;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;

class ApiExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious() ?? $exception;
        }
        
        $this->logger->error('API Exception: ' . $exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Validation errors (422)
        if ($exception instanceof UnprocessableEntityHttpException) {
            $validationErrors = $request->attributes->get('_validation_errors', []);
            
            if (!empty($validationErrors)) {
                $response = new JsonResponse(
                    ApiResponse::validationError($validationErrors),
                    422
                );
                $event->setResponse($response);
                return;
            }
        }

        // Authorization errors (403)
        if ($exception instanceof \App\Domain\Shared\Exception\UnauthorizedException) {
            $response = new JsonResponse(
                ApiResponse::error($exception->getMessage(), 403),
                403
            );
            $event->setResponse($response);
            return;
        }

        // Not found (404) — same response as missing resource to avoid enumeration
        if ($exception instanceof NotFoundException) {
            $response = new JsonResponse(
                ApiResponse::error($exception->getMessage(), 404),
                404
            );
            $event->setResponse($response);
            return;
        }

        // Conflict (409)
        if ($exception instanceof ConflictException) {
            $response = new JsonResponse(
                ApiResponse::error($exception->getMessage(), 409),
                409
            );
            $event->setResponse($response);
            return;
        }

        // Domain exceptions
        if ($exception instanceof DomainException) {
            $response = new JsonResponse(
                ApiResponse::error($exception->getMessage(), 400),
                400
            );
            $event->setResponse($response);
            return;
        }

        // Login blocked (e.g. unverified email) — must be before generic AuthenticationException
        if ($exception instanceof CustomUserMessageAccountStatusException) {
            $response = new JsonResponse(
                ApiResponse::error($exception->getMessage(), 401),
                401
            );
            $event->setResponse($response);
            return;
        }

        // Authentication errors (401)
        if ($exception instanceof AuthenticationException) {
            $response = new JsonResponse(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
            $event->setResponse($response);
            return;
        }

        // Access denied (403)
        if ($exception instanceof AccessDeniedException) {
            $response = new JsonResponse(
                ApiResponse::error('Доступ запрещён', 403),
                403
            );
            $event->setResponse($response);
            return;
        }

        // HTTP exceptions
        if ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse(
                ApiResponse::error(
                    $exception->getMessage(),
                    $exception->getStatusCode()
                ),
                $exception->getStatusCode()
            );
            $event->setResponse($response);
            return;
        }

        // Generic exceptions (500)
        $response = new JsonResponse(
            ApiResponse::error('Внутренняя ошибка сервера: ' . $exception->getMessage(), 500),
            500
        );
        $event->setResponse($response);
    }
}