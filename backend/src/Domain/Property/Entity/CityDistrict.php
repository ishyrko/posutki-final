<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'city_districts')]
#[ORM\UniqueConstraint(name: 'UNIQ_CITY_DISTRICTS_CITY_OFFICIAL', columns: ['city_id', 'official_name'])]
#[ORM\UniqueConstraint(name: 'UNIQ_CITY_DISTRICTS_CITY_SLUG', columns: ['city_id', 'slug'])]
class CityDistrict
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'integer', name: 'city_id')]
    private int $cityId;

    #[ORM\Column(type: 'string', length: 255, name: 'official_name')]
    private string $officialName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'name_prepositional')]
    private ?string $namePrepositional = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true, name: 'catalog_seo_text')]
    private ?string $catalogSeoText = null;

    /** @var list<array{question: string, answer: string}>|null */
    #[ORM\Column(type: 'json', nullable: true, name: 'catalog_faq')]
    private ?array $catalogFaq = null;

    #[ORM\Column(type: 'boolean', name: 'catalog_seo_visible', options: ['default' => false])]
    private bool $catalogSeoVisible = false;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(int $cityId, string $officialName, string $name, string $slug, ?string $namePrepositional = null)
    {
        $this->cityId = $cityId;
        $this->officialName = trim($officialName);
        $this->name = trim($name);
        $this->namePrepositional = $namePrepositional !== null ? trim($namePrepositional) : null;
        $this->slug = $slug;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCityId(): int
    {
        return $this->cityId;
    }

    public function getOfficialName(): string
    {
        return $this->officialName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamePrepositional(): ?string
    {
        return $this->namePrepositional;
    }

    public function setNamePrepositional(?string $namePrepositional): void
    {
        $this->namePrepositional = $namePrepositional !== null && trim($namePrepositional) !== ''
            ? trim($namePrepositional)
            : null;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getCatalogSeoText(): ?string
    {
        return $this->catalogSeoText;
    }

    public function setCatalogSeoText(?string $catalogSeoText): void
    {
        $this->catalogSeoText = $catalogSeoText;
    }

    /**
     * @return list<array{question: string, answer: string}>|null
     */
    public function getCatalogFaq(): ?array
    {
        return $this->catalogFaq;
    }

    /**
     * @param list<array{question: string, answer: string}>|null $catalogFaq
     */
    public function setCatalogFaq(?array $catalogFaq): void
    {
        $this->catalogFaq = $catalogFaq;
    }

    public function isCatalogSeoVisible(): bool
    {
        return $this->catalogSeoVisible;
    }

    public function setCatalogSeoVisible(bool $catalogSeoVisible): void
    {
        $this->catalogSeoVisible = $catalogSeoVisible;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
