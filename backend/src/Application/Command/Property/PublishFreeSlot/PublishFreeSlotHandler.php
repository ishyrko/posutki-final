<?php

declare(strict_types=1);

namespace App\Application\Command\Property\PublishFreeSlot;

use App\Application\Service\FreeListingLimitService;
use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Event\PropertyApprovedEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use Symfony\Component\Messenger\MessageBusInterface;

final class PublishFreeSlotHandler
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly FreeListingLimitService $freeListingLimitService,
        private readonly PropertyPlacementService $placementService,
        private readonly MessageBusInterface $notificationBus,
    ) {
    }

    public function __invoke(PublishFreeSlotCommand $command): void
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

        if ($property->getStatus() !== 'awaiting_payment') {
            throw new DomainException('Опубликовать бесплатно можно только объявление, ожидающее оплаты');
        }

        if (!$this->freeListingLimitService->canPublishFree($property)) {
            throw new DomainException('Лимит бесплатных объявлений исчерпан. Скройте другое объявление или купите для него VIP.');
        }

        $grantFreeTrial = $this->placementService->shouldGrantFreeTrial($property);
        $property->publishFreeSlot($grantFreeTrial);
        if ($grantFreeTrial) {
            $this->placementService->markFreePlacementTrialUsed($property);
        }

        $this->propertyRepository->save($property);
        $this->freeListingLimitService->maybeRefreshCityLimitAfterStatusChange($property);

        $this->notificationBus->dispatch(new PropertyApprovedEvent((string) $property->getId()->getValue()));
    }
}
