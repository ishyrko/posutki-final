<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'regions')]
class Region
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 50, nullable: true, name: 'external_id')]
    private ?string $externalId;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $code;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
