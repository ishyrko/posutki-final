<?php

declare(strict_types=1);

namespace App\Application\Command\User\SendEmailVerification;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;

readonly class SendEmailVerificationHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailerInterface $mailer,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {
    }

    public function __invoke(SendEmailVerificationCommand $command): void
    {
        $emailVo = Email::fromString($command->email);
        $user = $this->userRepository->findByEmail($emailVo)
            ?? $this->userRepository->findByPendingEmail($emailVo);

        if ($user === null) {
            return;
        }

        if ($user->getPendingEmail()?->getValue() === $command->email) {
            $token = $user->setPendingEmail(Email::fromString($command->email));
            $this->userRepository->save($user);
            $this->sendPendingConfirmationMail($user, $command->email, $token);

            return;
        }

        if ($user->getEmail()?->getValue() === $command->email && !$user->isVerified()) {
            $token = $user->requestEmailVerification();
            $this->userRepository->save($user);
            $this->sendPrimaryVerificationMail($user, $command->email, $token);
        }
    }

    private function sendPrimaryVerificationMail(\App\Domain\User\Entity\User $user, string $toEmail, string $token): void
    {
        $verifyUrl = $this->frontendUrl . '/verify-email?token=' . urlencode($token) . '&email=' . urlencode($toEmail);

        $email = (new MimeEmail())
            ->from($this->mailerFrom)
            ->to($toEmail)
            ->subject('Подтвердите email — RNB.by')
            ->html(
                '<p>Здравствуйте, ' . htmlspecialchars($user->getFirstName()) . '!</p>' .
                '<p>Подтвердите адрес электронной почты, перейдя по ссылке:</p>' .
                '<p><a href="' . htmlspecialchars($verifyUrl) . '">Подтвердить email</a></p>' .
                '<p>Ссылка действительна в течение 24 часов.</p>' .
                '<p>Если вы не регистрировались на RNB.by, проигнорируйте это письмо.</p>'
            );

        $this->mailer->send($email);
    }

    private function sendPendingConfirmationMail(\App\Domain\User\Entity\User $user, string $toEmail, string $token): void
    {
        $confirmUrl = $this->frontendUrl . '/confirm-email-change?token=' . urlencode($token) . '&email=' . urlencode($toEmail);

        $email = (new MimeEmail())
            ->from($this->mailerFrom)
            ->to($toEmail)
            ->subject('Подтвердите новый email — RNB.by')
            ->html(
                '<p>Здравствуйте, ' . htmlspecialchars($user->getFirstName()) . '!</p>' .
                '<p>Подтвердите новый адрес электронной почты, перейдя по ссылке:</p>' .
                '<p><a href="' . htmlspecialchars($confirmUrl) . '">Подтвердить email</a></p>' .
                '<p>Ссылка действительна в течение 24 часов.</p>' .
                '<p>Если вы не запрашивали смену email, проигнорируйте это письмо.</p>'
            );

        $this->mailer->send($email);
    }
}
