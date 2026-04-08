<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RequestDTOResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        
        // Only handle Request DTOs
        if (!$type || !str_ends_with($type, 'Request')) {
            return [];
        }

        // Only handle classes in our Request namespace
        if (!str_starts_with($type, 'App\\Presentation\\Api\\Request\\')) {
            return [];
        }

        try {
            // Deserialize JSON to DTO
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                $type,
                'json'
            );

            // Validate DTO
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }

                // Store validation errors in request attributes for exception listener
                $request->attributes->set('_validation_errors', $errors);
                
                throw new UnprocessableEntityHttpException('Ошибка валидации');
            }

            yield $dto;
        } catch (UnprocessableEntityHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new UnprocessableEntityHttpException('Некорректные данные запроса');
        }
    }
}