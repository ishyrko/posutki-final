<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'landmarks')]
#[ORM\UniqueConstraint(name: 'uniq_landmark_city_slug', columns: ['city_id', 'slug'])]
class Landmark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'city_id')]
    private int $cityId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(type: 'float')]
    private float $latitude;

    #[ORM\Column(type: 'float')]
    private float $longitude;

    #[ORM\Column(type: 'float', name: 'radius_km', options: ['default' => 1.5])]
    private float $radiusKm = 1.5;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'text', name: 'short_description', nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 512, name: 'image_url', nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'string', length: 255, name: 'catalog_location_phrase', nullable: true)]
    private ?string $catalogLocationPhrase = null;

    #[ORM\Column(type: 'string', length: 255, name: 'meta_title', nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: 'string', length: 512, name: 'meta_description', nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: 'boolean', name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'smallint', name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

    public function __construct(
        int $cityId,
        string $name,
        string $slug,
        float $latitude,
        float $longitude,
    ) {
        $this->cityId = $cityId;
        $this->name = trim($name);
        $this->slug = $slug;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCityId(): int
    {
        return $this->cityId;
    }

    public function setCityId(int $cityId): void
    {
        $this->cityId = $cityId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getRadiusKm(): float
    {
        return $this->radiusKm;
    }

    public function setRadiusKm(float $radiusKm): void
    {
        $this->radiusKm = $radiusKm;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): void
    {
        $this->shortDescription = $shortDescription;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getCatalogLocationPhrase(): ?string
    {
        return $this->catalogLocationPhrase;
    }

    public function setCatalogLocationPhrase(?string $catalogLocationPhrase): void
    {
        $this->catalogLocationPhrase = $catalogLocationPhrase;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): void
    {
        $this->metaTitle = $metaTitle;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): void
    {
        $this->metaDescription = $metaDescription;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
