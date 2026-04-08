<?php

declare(strict_types=1);

namespace App\Application\Command\ContactFeedback\Submit;

use App\Domain\ContactFeedback\Entity\ContactFeedback;
use App\Domain\ContactFeedback\Event\FeedbackSubmittedEvent;
use App\Domain\ContactFeedback\Repository\ContactFeedbackRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use Symfony\Component\Messenger\MessageBusInterface;

final class SubmitFeedbackHandler
{
    public function __construct(
        private readonly ContactFeedbackRepositoryInterface $contactFeedbackRepository,
        private readonly MessageBusInterface $notificationBus,
    ) {
    }

    public function __invoke(SubmitFeedbackCommand $command): array
    {
        $feedback = new ContactFeedback(
            name: $command->name,
            email: Email::fromString($command->email),
            subject: $command->subject,
            message: $command->message,
        );

        $this->contactFeedbackRepository->save($feedback);

        $this->notificationBus->dispatch(
            new FeedbackSubmittedEvent((string) $feedback->getId()->getValue())
        );

        return [
            'id' => (string) $feedback->getId()->getValue(),
        ];
    }
}
