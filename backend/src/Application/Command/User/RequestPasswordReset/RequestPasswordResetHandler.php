<?php

declare(strict_types=1);

namespace App\Application\Command\User\RequestPasswordReset;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;

readonly class RequestPasswordResetHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $user = $this->userRepository->findByEmail(Email::fromString($command->email));

        if (!$user) {
            // Silently return to prevent email enumeration
            return;
        }

        $token = $user->requestPasswordReset();
        $this->userRepository->save($user);

        $resetUrl = $this->frontendUrl . '/reset-password/confirm?token=' . $token . '&email=' . urlencode($command->email);

        $email = (new MimeEmail())
            ->from($this->mailerFrom)
            ->to($command->email)
            ->subject('Сброс пароля — RNB.by')
            ->html(
                '<p>Здравствуйте, ' . htmlspecialchars($user->getFirstName()) . '!</p>' .
                '<p>Вы запросили сброс пароля. Перейдите по ссылке ниже, чтобы установить новый пароль:</p>' .
                '<p><a href="' . $resetUrl . '">Сбросить пароль</a></p>' .
                '<p>Ссылка действительна в течение 1 часа.</p>' .
                '<p>Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.</p>'
            );

        $this->mailer->send($email);
    }
}
