<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Property\Entity\Property;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\User\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final readonly class PlacementMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $mailerFrom,
    ) {
    }

    public function sendPaymentSucceeded(
        PropertyPlacementPurchase $purchase,
        Property $property,
        User $owner,
        string $kindLabel,
        string $propertyUrl,
        string $paymentUrl,
        string $dashboardUrl,
    ): void {
        $ownerEmail = $owner->getEmail()?->getValue();
        if ($ownerEmail === null) {
            return;
        }

        $expiresAt = $purchase->getExpiresAt();
        $html = $this->twig->render('email/placement/payment_succeeded.html.twig', [
            'owner' => $owner,
            'property' => $property,
            'purchase' => $purchase,
            'kindLabel' => $kindLabel,
            'propertyUrl' => $propertyUrl,
            'paymentUrl' => $paymentUrl,
            'dashboardUrl' => $dashboardUrl,
            'expiresAtFormatted' => $expiresAt?->format('d.m.Y'),
        ]);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($ownerEmail)
            ->subject('Оплата размещения прошла успешно — ' . $property->getTitle())
            ->html($html);

        $this->mailer->send($email);
    }
}
