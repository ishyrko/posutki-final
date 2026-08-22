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

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'name_prepositional')]
    private ?string $namePrepositional = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'name_genitive')]
    private ?string $nameGenitive = null;

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

    #[ORM\Column(type: 'boolean', name: 'is_listing_suggested', options: ['default' => false])]
    private bool $isListingSuggested = false;

    #[ORM\Column(type: 'boolean', name: 'is_apartment_catalog', options: ['default' => false])]
    private bool $isApartmentCatalog = false;

    #[ORM\Column(type: 'text', nullable: true, name: 'catalog_seo_text')]
    private ?string $catalogSeoText = null;

    /** @var list<array{question: string, answer: string}>|null */
    #[ORM\Column(type: 'json', nullable: true, name: 'catalog_faq')]
    private ?array $catalogFaq = null;

    #[ORM\Column(type: 'boolean', name: 'catalog_seo_visible', options: ['default' => false])]
    private bool $catalogSeoVisible = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): void
    {
        $this->shortName = $shortName;
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

    public function getNameGenitive(): ?string
    {
        return $this->nameGenitive;
    }

    public function setNameGenitive(?string $nameGenitive): void
    {
        $this->nameGenitive = $nameGenitive !== null && trim($nameGenitive) !== ''
            ? trim($nameGenitive)
            : null;
    }

    public function getRuralCouncil(): ?string
    {
        return $this->ruralCouncil;
    }

    public function setRuralCouncil(?string $ruralCouncil): void
    {
        $this->ruralCouncil = $ruralCouncil;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getRegionDistrict(): ?RegionDistrict
    {
        return $this->regionDistrict;
    }

    public function setRegionDistrict(?RegionDistrict $regionDistrict): void
    {
        $this->regionDistrict = $regionDistrict;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function isListingSuggested(): bool
    {
        return $this->isListingSuggested;
    }

    public function setIsListingSuggested(bool $isListingSuggested): void
    {
        $this->isListingSuggested = $isListingSuggested;
    }

    public function isApartmentCatalog(): bool
    {
        return $this->isApartmentCatalog;
    }

    public function setIsApartmentCatalog(bool $isApartmentCatalog): void
    {
        $this->isApartmentCatalog = $isApartmentCatalog;
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

    public function __toString(): string
    {
        return $this->name;
    }
}
