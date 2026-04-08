<?php

declare(strict_types=1);

namespace App\Application\Query\Message\GetConversations;

use App\Application\DTO\ConversationDTO;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;

final class GetConversationsHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(GetConversationsQuery $query): array
    {
        $conversations = $this->conversationRepository->findByUser(
            $query->userId,
            $query->page,
            $query->limit,
        );
        $total = $this->conversationRepository->countByUser($query->userId);

        $dtos = [];
        foreach ($conversations as $conversation) {
            $property = $this->propertyRepository->findById(
                Id::fromString($conversation->getPropertyId())
            );
            $seller = $this->userRepository->findById(
                Id::fromString($conversation->getSellerId())
            );
            $buyer = $this->userRepository->findById(
                Id::fromString($conversation->getBuyerId())
            );

            $propertyImage = null;
            $images = $property?->getImages() ?? [];
            if (count($images) > 0) {
                $propertyImage = $images[0];
            }

            $dtos[] = ConversationDTO::fromEntity(
                $conversation,
                $query->userId,
                propertyTitle: $property?->getTitle(),
                propertyImage: $propertyImage,
                sellerName: $seller?->getFullName(),
                buyerName: $buyer?->getFullName(),
            );
        }

        return [
            'items' => $dtos,
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }
}
