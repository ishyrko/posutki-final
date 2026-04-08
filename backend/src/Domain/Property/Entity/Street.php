<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'streets')]
class Street
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(name: 'city_id', referencedColumnName: 'id', nullable: false)]
    private City $city;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $type;

    #[ORM\Column(type: 'string', length: 50, nullable: true, name: 'external_id')]
    private ?string $externalId;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCity(): City
    {
        return $this->city;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
