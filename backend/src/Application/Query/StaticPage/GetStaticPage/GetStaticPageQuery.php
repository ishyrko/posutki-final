<?php

declare(strict_types=1);

namespace App\Application\Query\StaticPage\GetStaticPage;

readonly class GetStaticPageQuery
{
    public function __construct(
        public string $slug,
    ) {
    }
}
