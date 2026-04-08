<?php

declare(strict_types=1);

namespace App\Application\Command\Property\PublishProperty;

use App\Domain\Property\Event\PropertySubmittedForModerationEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\Exception\DomainException;
use Symfony\Component\Messenger\MessageBusInterface;

final class PublishPropertyHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly MessageBusInterface $notificationBus,
    ) {
    }

    public function __invoke(PublishPropertyCommand $command): void
    {
        $propertyId = Id::fromString($command->propertyId);
        $userId = Id::fromString($command->userId);

        $property = $this->propertyRepository->findById($propertyId);

        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        if (!$property->getOwnerId()->equals($userId)) {
            throw new DomainException('Нет прав на публикацию этого объявления');
        }

        $property->publish();

        $this->propertyRepository->save($property);

        $this->notificationBus->dispatch(new PropertySubmittedForModerationEvent($command->propertyId));
    }
}