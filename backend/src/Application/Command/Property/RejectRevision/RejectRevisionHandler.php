<?php

declare(strict_types=1);

namespace App\Application\Command\Property\RejectRevision;

use App\Domain\Property\Event\PropertyRejectedEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\PropertyRevisionRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class RejectRevisionHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private PropertyRevisionRepositoryInterface $revisionRepository,
        private MessageBusInterface $notificationBus,
    ) {
    }

    public function __invoke(RejectRevisionCommand $command): void
    {
        $property = $this->propertyRepository->findById(Id::fromString($command->propertyId));
        if ($property === null) {
            throw new \InvalidArgumentException('Объявление не найдено');
        }

        $revision = $this->revisionRepository->findById(Id::fromString($command->revisionId)->getValue());
        if ($revision === null) {
            throw new \InvalidArgumentException('Версия не найдена');
        }

        if ($revision->getProperty()->getId()->getValue() !== $property->getId()->getValue()) {
            throw new \InvalidArgumentException('Версия не относится к этому объявлению');
        }

        $revision->reject($command->moderationComment);
        $this->revisionRepository->save($revision);

        $this->notificationBus->dispatch(new PropertyRejectedEvent($command->propertyId, $command->moderationComment));
    }
}
