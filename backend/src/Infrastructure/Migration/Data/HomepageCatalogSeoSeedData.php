<?php

declare(strict_types=1);

namespace App\Infrastructure\Migration\Data;

use App\Infrastructure\Migration\Data\HomepageCatalogSeo\BrestOblastSeo;
use App\Infrastructure\Migration\Data\HomepageCatalogSeo\GomelOblastSeo;
use App\Infrastructure\Migration\Data\HomepageCatalogSeo\GrodnoOblastSeo;
use App\Infrastructure\Migration\Data\HomepageCatalogSeo\MinskOblastSeo;
use App\Infrastructure\Migration\Data\HomepageCatalogSeo\MogilevOblastSeo;
use App\Infrastructure\Migration\Data\HomepageCatalogSeo\VitebskOblastSeo;

/**
 * Catalog SEO + FAQ for homepage cities that had empty catalog_seo_text.
 */
final class HomepageCatalogSeoSeedData
{
    /**
     * @return array<string, array{seo: string, faq: list<array{question: string, answer: string}>}>
     */
    public static function cityContent(): array
    {
        return array_merge(
            MinskOblastSeo::cities(),
            BrestOblastSeo::cities(),
            GrodnoOblastSeo::cities(),
            VitebskOblastSeo::cities(),
            GomelOblastSeo::cities(),
            MogilevOblastSeo::cities(),
        );
    }

    /**
     * @return array<string, array<int, array{seo: string, faq: list<array{question: string, answer: string}>}>>
     */
    public static function roomContent(): array
    {
        return array_merge(
            MinskOblastSeo::rooms(),
            BrestOblastSeo::rooms(),
            GrodnoOblastSeo::rooms(),
            VitebskOblastSeo::rooms(),
            GomelOblastSeo::rooms(),
            MogilevOblastSeo::rooms(),
        );
    }
}
