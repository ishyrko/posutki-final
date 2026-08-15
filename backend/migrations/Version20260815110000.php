<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Catalog SEO copy for Zhodino. Geographic claims follow the official city portal
 * zhodino.gov.by (rivers, railway, Prospekt Mira / BELAZ, city park).
 */
final class Version20260815110000 extends AbstractMigration
{
    private const CITY_SLUG = 'zhodino';

    public function getDescription(): string
    {
        return 'Fill hidden catalog SEO text and FAQ for Zhodino';
    }

    public function up(Schema $schema): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM cities WHERE slug = ?',
            [self::CITY_SLUG],
        );
        if ($exists === 0) {
            throw new \RuntimeException('Cannot fill city catalog content: missing city with slug zhodino.');
        }

        $content = $this->cityContent();
        $normalizer = $this->catalogContentNormalizer();
        $seo = $normalizer->normalizeSeoText($content['seo']);
        $faq = $normalizer->normalizeFaq($content['faq']);
        $faqJson = json_encode(
            $faq,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $this->addSql(
            'UPDATE cities
             SET catalog_seo_text = ?, catalog_faq = ?, catalog_seo_visible = 0
             WHERE slug = ?',
            [$seo, $faqJson, self::CITY_SLUG],
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Previous Zhodino SEO text, FAQ and visibility values were overwritten and cannot be restored safely.',
        );
    }

    private function catalogContentNormalizer(): CatalogPlaceContentNormalizer
    {
        return new CatalogPlaceContentNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );
    }

    /**
     * @return array{seo: string, faq: list<array{question: string, answer: string}>}
     */
    private function cityContent(): array
    {
        return [
            'seo' => <<<'HTML'
<p>Жодино стоит на реках Плиса и Жодинка и лежит на железнодорожной магистрали Брест — Москва. При выборе квартиры на сутки сначала определите цель поездки: проспект Мира и городской парк культуры и отдыха удобны для ориентации в центре, а для рабочей поездки сверяйте адрес с конкретным предприятием, в том числе с БелАЗом.</p>
<p>Если вы приезжаете поездом, проверьте название станции в билете: в городе есть станция Жодино, открытая в 1871 году, и станция Жодино-Южный. Затем сравните точный адрес объявления на карте. Если в программу входит и столица, отдельно посмотрите квартиры <a href="/kvartiry/">в Минске</a>. Свободные даты, стоимость и время заселения подтвердите у владельца.</p>
HTML,
            'faq' => [
                [
                    'question' => 'Как выбрать квартиру на сутки в Жодино?',
                    'answer' => 'Ориентируйтесь на цель поездки: для прогулок сравните адрес с проспектом Мира и городским парком, для работы — с конкретным предприятием. Точное расположение дома проверяйте на карте.',
                ],
                [
                    'question' => 'Как выбрать жильё при поездке в Жодино на поезде?',
                    'answer' => 'В городе две станции: Жодино и Жодино-Южный. Сверьте название в билете, затем проверьте маршрут от нужной станции до адреса квартиры и заранее согласуйте время заселения.',
                ],
                [
                    'question' => 'На каких реках расположен город?',
                    'answer' => 'Жодино располагается на реках Плиса и Жодинка. Название города связано с речкой Жодинка.',
                ],
                [
                    'question' => 'Что согласовать с владельцем до поездки?',
                    'answer' => 'Подтвердите свободные даты, итоговую стоимость, число гостей, точный адрес, время заселения и порядок передачи ключей.',
                ],
            ],
        ];
    }
}
