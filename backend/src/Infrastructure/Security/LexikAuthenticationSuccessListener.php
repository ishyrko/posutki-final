<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
final class LexikAuthenticationSuccessListener
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $event->setData($event->getData() + [
            'refreshToken' => $this->refreshTokenService->issue($user),
        ]);
    }
}
