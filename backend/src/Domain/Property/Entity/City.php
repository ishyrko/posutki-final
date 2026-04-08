<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cities')]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255, name: 'short_name')]
    private string $shortName;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'rural_council')]
    private ?string $ruralCouncil;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $latitude;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $longitude;

    #[ORM\ManyToOne(targetEntity: RegionDistrict::class)]
    #[ORM\JoinColumn(name: 'region_district_id', referencedColumnName: 'id', nullable: true)]
    private ?RegionDistrict $regionDistrict = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true, name: 'external_id')]
    private ?string $externalId;

    #[ORM\Column(type: 'boolean', name: 'is_main', options: ['default' => false])]
    private bool $isMain = false;

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

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function getRuralCouncil(): ?string
    {
        return $this->ruralCouncil;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function getRegionDistrict(): ?RegionDistrict
    {
        return $this->regionDistrict;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
