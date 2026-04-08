<?php

declare(strict_types=1);

namespace App\Application\Notification\Property;

use App\Domain\Property\Event\PropertyApprovedEvent;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\PropertyMailer;

final readonly class PropertyApprovedHandler
{
    public function __construct(
        private PropertyRepositoryInterface $propertyRepository,
        private UserRepositoryInterface $userRepository,
        private PropertyMailer $mailer,
    ) {
    }

    public function __invoke(PropertyApprovedEvent $event): void
    {
        $property = $this->propertyRepository->findById(Id::fromString($event->propertyId));
        if ($property === null) {
            return;
        }

        $owner = $this->userRepository->findById($property->getOwnerId());
        if ($owner === null) {
            return;
        }

        $this->mailer->sendApproved($property, $owner);
    }
}
