<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Property\Entity\Property;
use App\Domain\Review\Entity\Review;
use App\Domain\User\Entity\User;
use App\Infrastructure\Service\FrontendUrlBuilder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class ReviewMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $mailerFrom,
        private FrontendUrlBuilder $frontendUrls,
    ) {
    }

    public function sendApprovedToOwner(User $owner, Property $property, Review $review): void
    {
        $recipientEmail = $owner->getEmail()?->getValue();
        if ($recipientEmail === null || !$owner->isVerified()) {
            return;
        }

        $this->send(
            to: $recipientEmail,
            subject: 'Новый отзыв — ' . $property->getTitle(),
            template: 'email/review/approved_owner.html.twig',
            context: [
                'owner' => $owner,
                'property' => $property,
                'review' => $review,
                'reviewsUrl' => $this->frontendUrls->propertyReviews($property->getId()->getValue()),
                'propertyUrl' => $this->frontendUrls->publicPropertyForListing($property),
            ],
        );
    }

    public function sendReplyToAuthor(User $author, Property $property, Review $review): void
    {
        $recipientEmail = $author->getEmail()?->getValue();
        if ($recipientEmail === null || !$author->isVerified()) {
            return;
        }

        $propertyUrl = $this->frontendUrls->publicPropertyForListing($property) . '#reviews';

        $this->send(
            to: $recipientEmail,
            subject: 'Ответ на ваш отзыв — ' . $property->getTitle(),
            template: 'email/review/reply_author.html.twig',
            context: [
                'author' => $author,
                'property' => $property,
                'review' => $review,
                'propertyUrl' => $propertyUrl,
            ],
        );
    }

    private function send(string $to, string $subject, string $template, array $context): void
    {
        $html = $this->twig->render($template, $context);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($to)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }
}
