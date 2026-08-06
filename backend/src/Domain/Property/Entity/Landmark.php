<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'landmarks')]
#[ORM\UniqueConstraint(name: 'uniq_landmark_city_slug', columns: ['city_id', 'slug'])]
class Landmark
{
    public const PROXIMITY_RADIUS_KM = 2.0;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'city_id')]
    private int $cityId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, name: 'name_genitive')]
    private string $nameGenitive;

    #[ORM\Column(type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'text', name: 'short_description', nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $address = null;

    /** @var list<array{label: string, value: string}>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $facts = null;

    /** @var list<string>|null */
    #[ORM\Column(type: 'json', name: 'guest_tips', nullable: true)]
    private ?array $guestTips = null;

    #[ORM\Column(type: 'string', length: 512, name: 'image_url', nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'boolean', name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'smallint', name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

    public function __construct(
        int $cityId,
        string $name,
        string $slug,
        string $nameGenitive = '',
        ?float $latitude = null,
        ?float $longitude = null,
    ) {
        $this->cityId = $cityId;
        $this->name = trim($name);
        $this->slug = $slug;
        $this->nameGenitive = trim($nameGenitive);
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

    public function getNameGenitive(): string
    {
        return $this->nameGenitive;
    }

    public function setNameGenitive(string $nameGenitive): void
    {
        $this->nameGenitive = trim($nameGenitive);
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address !== null && trim($address) !== '' ? trim($address) : null;
    }

    /**
     * @return list<array{label: string, value: string}>|null
     */
    public function getFacts(): ?array
    {
        return $this->facts;
    }

    /**
     * @param list<array{label: string, value: string}>|null $facts
     */
    public function setFacts(?array $facts): void
    {
        $this->facts = $facts;
    }

    /**
     * @return list<string>|null
     */
    public function getGuestTips(): ?array
    {
        return $this->guestTips;
    }

    /**
     * @param list<string>|null $guestTips
     */
    public function setGuestTips(?array $guestTips): void
    {
        $this->guestTips = $guestTips;
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
