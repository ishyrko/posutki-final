<?php

declare(strict_types=1);

namespace App\Domain\Article\Entity;

use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\{Id, Slug};
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'articles')]
#[ORM\Index(columns: ['status', 'published_at'])]
class Article
{
    #[ORM\Id]
    #[ORM\Column(type: 'id')]
    #[ORM\GeneratedValue]
    private Id $id;

    #[ORM\Column(type: 'id', name: 'author_id')]
    private Id $authorId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'slug', length: 255, unique: true)]
    private Slug $slug;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'text')]
    private string $excerpt;

    #[ORM\Column(type: 'string', length: 255, nullable: true, name: 'cover_image')]
    private ?string $coverImage = null;

    #[ORM\ManyToOne(targetEntity: ArticleCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?ArticleCategory $category = null;

    #[ORM\Column(type: 'json')]
    private array $tags = [];

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'draft';

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'published_at')]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $views = 0;

    public function __construct(
        Id $authorId,
        string $title,
        Slug $slug,
        string $content,
        string $excerpt,
        ?string $coverImage = null,
        ?ArticleCategory $category = null,
        array $tags = [],
        string $status = 'draft'
    ) {
        $this->authorId = $authorId;
        $this->title = $title;
        $this->slug = $slug;
        $this->content = $content;
        $this->excerpt = $excerpt;
        $this->coverImage = $this->normalizeCoverImage($coverImage);
        $this->category = $category;
        $this->tags = $tags;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    // Domain Methods
    public function publish(): void
    {
        if ($this->status === 'published') {
            throw new DomainException('Статья уже опубликована');
        }

        $this->status = 'published';
        $this->publishedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function draft(): void
    {
        $this->status = 'draft';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function incrementViews(): void
    {
        $this->views++;
    }

    public function update(
        ?string $title = null,
        ?Slug $slug = null,
        ?string $content = null,
        ?string $excerpt = null,
        ?string $coverImage = null,
        ?ArticleCategory $category = null,
        ?array $tags = null,
    ): void {
        if ($title !== null) $this->title = $title;
        if ($slug !== null) $this->slug = $slug;
        if ($content !== null) $this->content = $content;
        if ($excerpt !== null) $this->excerpt = $excerpt;
        if ($coverImage !== null) $this->coverImage = $this->normalizeCoverImage($coverImage);
        if ($category !== null) $this->category = $category;
        if ($tags !== null) $this->tags = $tags;
        
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function delete(): void
    {
        $this->status = 'deleted';
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isAuthoredBy(string $userId): bool
    {
        return (string) $this->authorId->getValue() === $userId;
    }

    public function getAuthorId(): Id
    {
        return $this->authorId;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function getCoverImage(): ?string
    {
        if ($this->coverImage === null || $this->coverImage === '') {
            return null;
        }

        $stored = $this->coverImage;

        // Legacy seed / external URLs stored as-is in the column
        if (preg_match('#^https?://#i', $stored) === 1) {
            return $stored;
        }

        // Legacy rows: full web path (EasyAdmin FileUploadType needs filename-only in the column)
        if (str_starts_with($stored, '/uploads/')) {
            if (preg_match('#^/uploads/[^/]+$#', $stored) === 1) {
                return '/uploads/articles/' . basename($stored);
            }

            return $stored;
        }

        return '/uploads/articles/' . ltrim($stored, '/');
    }

    public function getCategory(): ?ArticleCategory
    {
        return $this->category;
    }

    public function setCategory(?ArticleCategory $category): void
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setSlugFromString(string $slug): void
    {
        $this->slug = Slug::fromString($slug);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setExcerpt(string $excerpt): void
    {
        $this->excerpt = $excerpt;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setCoverImage(?string $coverImage): void
    {
        $this->coverImage = $this->normalizeCoverImage($coverImage);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function normalizeCoverImage(?string $coverImage): ?string
    {
        if ($coverImage === null) {
            return null;
        }

        $coverImage = trim($coverImage);
        if ($coverImage === '') {
            return null;
        }

        // Keep external URLs for legacy content; API exposes them via getCoverImage()
        if (preg_match('#^https?://#i', $coverImage) === 1) {
            return $coverImage;
        }

        // Persist local covers as paths relative to public/uploads/articles/ (filename or subpath).
        // EasyAdmin ImageField + StringToFileTransformer join upload_dir with this string — full /uploads/... breaks edit/save.
        if (str_starts_with($coverImage, '/uploads/')) {
            $rest = substr($coverImage, strlen('/uploads/'));
            if (str_starts_with($rest, 'articles/')) {
                return substr($rest, strlen('articles/'));
            }

            return basename($rest);
        }

        if (str_starts_with($coverImage, 'uploads/')) {
            return $this->normalizeCoverImage('/' . $coverImage);
        }

        if (!str_starts_with($coverImage, '/')) {
            return ltrim($coverImage, '/');
        }

        throw new DomainException('Обложка должна ссылаться на загруженный файл');
    }
}
