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
        $expiresAtFormatted = $expiresAt === null
            ? null
            : ($purchase->isBoost() ? $expiresAt->format('d.m.Y H:i') : $expiresAt->format('d.m.Y'));
        $html = $this->twig->render('email/placement/payment_succeeded.html.twig', [
            'owner' => $owner,
            'property' => $property,
            'purchase' => $purchase,
            'kindLabel' => $kindLabel,
            'propertyUrl' => $propertyUrl,
            'paymentUrl' => $paymentUrl,
            'dashboardUrl' => $dashboardUrl,
            'expiresAtFormatted' => $expiresAtFormatted,
        ]);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($ownerEmail)
            ->subject('Оплата размещения прошла успешно — ' . $property->getTitle())
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * @param array{phoneViews: int, messages: int, bookingInquiries: int, favorites: int, total: int}|null $recentEngagement
     */
    public function sendVipExpiringSoon(
        Property $property,
        User $owner,
        string $propertyUrl,
        string $listingsUrl,
        string $dashboardUrl,
        ?array $recentEngagement,
        bool $freeSlotUnavailable = false,
        ?string $limitIntro = null,
    ): void {
        $ownerEmail = $owner->getEmail()?->getValue();
        if ($ownerEmail === null) {
            return;
        }

        $expiresAt = $property->getPlacementLevelExpiresAt();
        $level = $property->getPlacementBaseLevel();
        $html = $this->twig->render('email/placement/vip_expiring_soon.html.twig', [
            'owner' => $owner,
            'property' => $property,
            'level' => $level,
            'isTrial' => $property->isPlacementIsTrial(),
            'propertyUrl' => $propertyUrl,
            'listingsUrl' => $listingsUrl,
            'dashboardUrl' => $dashboardUrl,
            'expiresAtFormatted' => $expiresAt?->format('d.m.Y H:i'),
            'recentEngagement' => $recentEngagement,
            'freeSlotUnavailable' => $freeSlotUnavailable,
            'limitIntro' => $limitIntro,
        ]);

        $subject = $freeSlotUnavailable
            ? 'VIP скоро истекает — нет свободного бесплатного слота — ' . $property->getTitle()
            : 'VIP истекает ' . ($expiresAt?->format('d.m.Y H:i') ?? '') . ' — ' . $property->getTitle();

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($ownerEmail)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * @param array{phoneViews: int, messages: int, bookingInquiries: int, favorites: int, total: int}|null $recentEngagement
     */
    public function sendVipExpired(
        Property $property,
        User $owner,
        int $level,
        bool $isTrial,
        ?\DateTimeImmutable $expiresAt,
        string $propertyUrl,
        string $listingsUrl,
        string $dashboardUrl,
        ?array $recentEngagement,
        bool $hiddenDueToFreeLimit = false,
        ?string $limitIntro = null,
    ): void {
        $ownerEmail = $owner->getEmail()?->getValue();
        if ($ownerEmail === null) {
            return;
        }

        $html = $this->twig->render('email/placement/vip_expired.html.twig', [
            'owner' => $owner,
            'property' => $property,
            'level' => $level,
            'isTrial' => $isTrial,
            'propertyUrl' => $propertyUrl,
            'listingsUrl' => $listingsUrl,
            'dashboardUrl' => $dashboardUrl,
            'expiresAtFormatted' => $expiresAt?->format('d.m.Y H:i'),
            'recentEngagement' => $recentEngagement,
            'hiddenDueToFreeLimit' => $hiddenDueToFreeLimit,
            'limitIntro' => $limitIntro,
        ]);

        $subject = $hiddenDueToFreeLimit
            ? 'Объявление скрыто — нет свободного бесплатного слота — ' . $property->getTitle()
            : 'VIP истёк — ' . $property->getTitle();

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($ownerEmail)
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }
}
