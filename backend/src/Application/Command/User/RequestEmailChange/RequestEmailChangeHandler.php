<?php

declare(strict_types=1);

namespace App\Application\Command\User\RequestEmailChange;

use App\Domain\Shared\Exception\ConflictException;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;

final class RequestEmailChangeHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly MailerInterface $mailer,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom,
    ) {
    }

    public function __invoke(RequestEmailChangeCommand $command): void
    {
        $userId = Id::fromString($command->userId);
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new DomainException('Пользователь не найден');
        }

        $newEmail = Email::fromString($command->email);

        if ($user->getEmail() !== null
            && $user->getEmail()->getValue() === $newEmail->getValue()
            && $user->isVerified()
        ) {
            throw new DomainException('Этот email уже привязан к вашему аккаунту');
        }

        $existingByEmail = $this->userRepository->findByEmail($newEmail);
        if ($existingByEmail !== null && $existingByEmail->getId()->getValue() !== $user->getId()->getValue()) {
            throw new ConflictException('Пользователь с таким email уже существует');
        }

        $existingByPending = $this->userRepository->findByPendingEmail($newEmail);
        if ($existingByPending !== null && $existingByPending->getId()->getValue() !== $user->getId()->getValue()) {
            throw new ConflictException('Этот email уже используется для подтверждения другим аккаунтом');
        }

        $token = $user->setPendingEmail($newEmail);
        $this->userRepository->save($user);

        $confirmUrl = $this->frontendUrl . '/confirm-email-change?token=' . urlencode($token) . '&email=' . urlencode($newEmail->getValue());

        $email = (new MimeEmail())
            ->from($this->mailerFrom)
            ->to($newEmail->getValue())
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
