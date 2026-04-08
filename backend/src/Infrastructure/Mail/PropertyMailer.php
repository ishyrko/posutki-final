<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Property\Entity\Property;
use App\Domain\User\Entity\User;
use App\Infrastructure\Service\FrontendUrlBuilder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class PropertyMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $mailerFrom,
        private FrontendUrlBuilder $frontendUrls,
        private array $adminEmails,
    ) {
    }

    public function sendSubmittedForModeration(Property $property, User $owner): void
    {
        $ownerEmail = $owner->getEmail()?->getValue();
        $context = [
            'owner' => $owner,
            'property' => $property,
            'dashboardUrl' => $this->frontendUrls->cabinet(),
        ];

        if ($ownerEmail !== null) {
            $this->send(
                to: $ownerEmail,
                subject: 'Ваше объявление отправлено на модерацию — RNB.by',
                template: 'email/property/submitted_owner.html.twig',
                context: $context,
            );
        }

        if (!empty($this->adminEmails)) {
            $adminContext = array_merge($context, [
                'adminUrl' => $this->frontendUrls->admin(),
            ]);
            foreach ($this->adminEmails as $adminEmail) {
                $this->send(
                    to: $adminEmail,
                    subject: 'Новое объявление ждёт модерации — ' . $property->getTitle(),
                    template: 'email/property/submitted_admin.html.twig',
                    context: $adminContext,
                );
            }
        }
    }

    public function sendApproved(Property $property, User $owner): void
    {
        $ownerEmail = $owner->getEmail()?->getValue();
        if ($ownerEmail === null) {
            return;
        }

        $this->send(
            to: $ownerEmail,
            subject: 'Ваше объявление опубликовано — ' . $property->getTitle(),
            template: 'email/property/approved.html.twig',
            context: [
                'owner' => $owner,
                'property' => $property,
                'propertyUrl' => $this->frontendUrls->publicProperty(
                    $property->getDealType(),
                    $property->getType(),
                    $property->getId()->getValue(),
                ),
                'dashboardUrl' => $this->frontendUrls->cabinet(),
            ],
        );
    }

    public function sendRejected(Property $property, User $owner, ?string $moderationComment): void
    {
        $ownerEmail = $owner->getEmail()?->getValue();
        if ($ownerEmail === null) {
            return;
        }

        $this->send(
            to: $ownerEmail,
            subject: 'Объявление не прошло модерацию — ' . $property->getTitle(),
            template: 'email/property/rejected.html.twig',
            context: [
                'owner' => $owner,
                'property' => $property,
                'moderationComment' => $moderationComment,
                'editUrl' => $this->frontendUrls->editProperty($property->getId()->getValue()),
                'dashboardUrl' => $this->frontendUrls->cabinet(),
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
