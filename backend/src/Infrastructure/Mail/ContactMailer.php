<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\ContactFeedback\Entity\ContactFeedback;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class ContactMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $mailerFrom,
        private array $adminEmails,
    ) {
    }

    public function sendFeedbackSubmitted(ContactFeedback $feedback): void
    {
        if (empty($this->adminEmails)) {
            return;
        }

        foreach ($this->adminEmails as $adminEmail) {
            $this->send(
                to: $adminEmail,
                subject: 'Новая заявка обратной связи — ' . $feedback->getSubject(),
                template: 'email/contact/feedback.html.twig',
                context: [
                    'feedback' => $feedback,
                ],
            );
        }
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
