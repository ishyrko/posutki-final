<?php

declare(strict_types=1);

namespace App\Domain\Property\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'nationwide_house_catalog_contents')]
class NationwideHouseCatalogContent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'text', nullable: true, name: 'catalog_seo_text')]
    private ?string $catalogSeoText = null;

    /** @var list<array{question: string, answer: string}>|null */
    #[ORM\Column(type: 'json', nullable: true, name: 'catalog_faq')]
    private ?array $catalogFaq = null;

    #[ORM\Column(type: 'boolean', name: 'catalog_seo_visible', options: ['default' => false])]
    private bool $catalogSeoVisible = false;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getScopeLabel(): string
    {
        return 'Беларусь';
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
        return 'Усадьбы в Беларуси';
    }
}
