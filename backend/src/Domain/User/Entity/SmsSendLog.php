<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Shared\ValueObject\Id;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sms_send_log')]
#[ORM\Index(name: 'IDX_SMS_SEND_LOG_PHONE_CREATED', columns: ['phone', 'created_at'])]
#[ORM\Index(name: 'IDX_SMS_SEND_LOG_IP_CREATED', columns: ['ip', 'created_at'])]
#[ORM\Index(name: 'IDX_SMS_SEND_LOG_CREATED', columns: ['created_at'])]
class SmsSendLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 45)]
    private string $ip;

    #[ORM\Column(type: 'integer', nullable: true, name: 'user_id')]
    private ?int $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $channel;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $phone,
        string $ip,
        ?int $userId,
        string $channel,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->phone = $phone;
        $this->ip = $ip;
        $this->userId = $userId;
        $this->channel = $channel;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
