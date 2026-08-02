<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'property_geocoder_results')]
#[ORM\UniqueConstraint(name: 'UNIQ_PROPERTY_GEOCODER_PROPERTY', columns: ['property_id'])]
class PropertyGeocoderResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'property_id')]
    private int $propertyId;

    #[ORM\Column(type: 'float')]
    private float $latitude;

    #[ORM\Column(type: 'float')]
    private float $longitude;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $response;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(int $propertyId, float $latitude, float $longitude, array $response)
    {
        $this->propertyId = $propertyId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->response = $response;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function matchesCoordinates(float $latitude, float $longitude): bool
    {
        return sprintf('%F,%F', $this->longitude, $this->latitude)
            === sprintf('%F,%F', $longitude, $latitude);
    }

    /**
     * @param array<string, mixed> $response
     */
    public function update(float $latitude, float $longitude, array $response): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->response = $response;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
