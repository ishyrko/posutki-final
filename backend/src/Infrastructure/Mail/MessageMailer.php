<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Property\Entity\Property;
use App\Domain\User\Entity\User;
use App\Infrastructure\Service\FrontendUrlBuilder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class MessageMailer
{
    private const int PREVIEW_MAX_LENGTH = 500;

    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $mailerFrom,
        private FrontendUrlBuilder $frontendUrls,
    ) {
    }

    public function sendNewMessage(
        User $recipient,
        User $sender,
        Property $property,
        string $messageText,
    ): void {
        $recipientEmail = $recipient->getEmail()?->getValue();
        if ($recipientEmail === null) {
            return;
        }

        $preview = mb_strlen($messageText) > self::PREVIEW_MAX_LENGTH
            ? mb_substr($messageText, 0, self::PREVIEW_MAX_LENGTH) . '…'
            : $messageText;

        $this->send(
            to: $recipientEmail,
            subject: 'Новое сообщение — ' . $property->getTitle(),
            template: 'email/message/received.html.twig',
            context: [
                'recipient' => $recipient,
                'sender' => $sender,
                'property' => $property,
                'messagePreview' => $preview,
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
