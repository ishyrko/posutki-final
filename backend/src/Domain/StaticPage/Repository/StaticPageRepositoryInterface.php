<?php

declare(strict_types=1);

namespace App\Domain\StaticPage\Repository;

use App\Domain\StaticPage\Entity\StaticPage;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\Shared\ValueObject\Slug;

interface StaticPageRepositoryInterface
{
    public function save(StaticPage $page): void;

    public function findById(Id $id): ?StaticPage;

    public function findBySlug(Slug $slug): ?StaticPage;

    public function delete(StaticPage $page): void;
}
