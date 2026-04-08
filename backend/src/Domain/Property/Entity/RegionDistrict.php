<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'region_districts')]
class RegionDistrict
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id', nullable: false)]
    private Region $region;

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

    public function getRegion(): Region
    {
        return $this->region;
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
