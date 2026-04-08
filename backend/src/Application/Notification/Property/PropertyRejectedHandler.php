<?php

declare(strict_types=1);

namespace App\Application\Notification\Property;

use App\Domain\Property\Event\PropertyRejectedEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\PropertyMailer;

final readonly class PropertyRejectedHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private UserRepositoryInterface $userRepository,
        private PropertyMailer $mailer,
    ) {
    }

    public function __invoke(PropertyRejectedEvent $event): void
    {
        $property = $this->propertyRepository->findById(Id::fromString($event->propertyId));
        if ($property === null) {
            return;
        }

        $owner = $this->userRepository->findById($property->getOwnerId());
        if ($owner === null) {
            return;
        }

        $this->mailer->sendRejected($property, $owner, $event->moderationComment);
    }
}
