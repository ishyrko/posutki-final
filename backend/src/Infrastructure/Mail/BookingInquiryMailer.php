<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\Property\Entity\Property;
use App\Domain\User\Entity\User;
use App\Infrastructure\Service\FrontendUrlBuilder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class BookingInquiryMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $mailerFrom,
        private FrontendUrlBuilder $frontendUrls,
    ) {
    }

    public function sendToOwner(User $owner, Property $property, BookingInquiry $inquiry): void
    {
        $recipientEmail = $owner->getEmail()?->getValue();
        if ($recipientEmail === null || !$owner->isVerified()) {
            return;
        }

        $this->send(
            to: $recipientEmail,
            subject: 'Новая заявка на бронирование — ' . $property->getTitle(),
            template: 'email/booking-inquiry/received.html.twig',
            context: [
                'owner' => $owner,
                'property' => $property,
                'inquiry' => $inquiry,
                'messagesUrl' => $this->frontendUrls->messages(),
                'propertyUrl' => $this->frontendUrls->publicPropertyForListing($property),
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
