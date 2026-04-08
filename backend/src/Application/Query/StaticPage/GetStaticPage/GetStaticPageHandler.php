<?php

declare(strict_types=1);

namespace App\Application\Query\StaticPage\GetStaticPage;

use App\Application\DTO\StaticPageDTO;
use App\Domain\Shared\ValueObject\Slug;
use App\Domain\StaticPage\Repository\StaticPageRepositoryInterface;

readonly class GetStaticPageHandler
{
    public function __construct(
        private StaticPageRepositoryInterface $staticPageRepository,
    ) {
    }

    public function __invoke(GetStaticPageQuery $query): ?StaticPageDTO
    {
        $page = $this->staticPageRepository->findBySlug(Slug::fromString($query->slug));

        if ($page === null) {
            return null;
        }

        return StaticPageDTO::fromEntity($page);
    }
}
