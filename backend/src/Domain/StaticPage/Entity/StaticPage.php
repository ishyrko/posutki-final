<?php

declare(strict_types=1);

namespace App\Domain\StaticPage\Entity;

use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'static_pages')]
class StaticPage
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'slug', length: 255)]
    private Slug $slug;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'meta_title')]
    private ?string $metaTitle = null;

    #[ORM\Column(type: 'text', nullable: true, name: 'meta_description')]
    private ?string $metaDescription = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Slug $slug,
        string $title,
        string $content,
        ?string $metaTitle = null,
        ?string $metaDescription = null,
    ) {
        $this->slug = $slug;
        $this->title = $title;
        $this->content = $content;
        $this->metaTitle = $metaTitle;
        $this->metaDescription = $metaDescription;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setSlugFromString(string $slug): void
    {
        $this->slug = Slug::fromString($slug);
        $this->touch();
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->touch();
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->touch();
    }

    public function setMetaTitle(?string $metaTitle): void
    {
        $this->metaTitle = $metaTitle;
        $this->touch();
    }

    public function setMetaDescription(?string $metaDescription): void
    {
        $this->metaDescription = $metaDescription;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
