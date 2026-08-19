<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Catalog SEO copy for Nesvizh and Glubokoe.
 *
 * Geographic claims: niasvizh.by (palace location, Gorodeya 14 km),
 * minoblturism.gov.by (Usha ponds, Radziwill palace), niasvizh-kasciol.by
 * (Corpus Christi church), glubokoe.vitebsk-region.gov.by (cherry festival),
 * belarus.travel (Trinity church), glubokoe-blag.cerkov.ru (Nativity cathedral).
 */
final class Version20260820120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fill catalog SEO text and FAQ for Nesvizh and Glubokoe';
    }

    public function up(Schema $schema): void
    {
        $content = $this->cityContent();
        $existingSlugs = $this->connection->fetchFirstColumn(
            'SELECT slug FROM cities WHERE slug IN (?)',
            [array_keys($content)],
            [\Doctrine\DBAL\ArrayParameterType::STRING],
        );
        $missingSlugs = array_values(array_diff(array_keys($content), $existingSlugs));

        if ($missingSlugs !== []) {
            throw new \RuntimeException(sprintf(
                'Cannot fill city catalog content: missing cities with slugs %s.',
                implode(', ', $missingSlugs),
            ));
        }

        $normalizer = $this->catalogContentNormalizer();

        foreach ($content as $slug => $cityContent) {
            $seo = $normalizer->normalizeSeoText($cityContent['seo']);
            $faqJson = json_encode(
                $normalizer->normalizeFaq($cityContent['faq']),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            $this->addSql(
                'UPDATE cities
                 SET catalog_seo_text = ?, catalog_faq = ?, catalog_seo_visible = 1
                 WHERE slug = ?',
                [$seo, $faqJson, $slug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Previous Nesvizh and Glubokoe SEO text, FAQ and visibility cannot be restored safely.',
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
     * @return array<string, array{
     *     seo: string,
     *     faq: list<array{question: string, answer: string}>
     * }>
     */
    private function cityContent(): array
    {
        return [
            'nesvizh' => [
                'seo' => <<<'HTML'
<p>Дворцово-парковый ансамбль Радзивиллов стоит в северо-восточной части Несвижа, у прудов реки Уши, и входит в Национальный историко-культурный музей-заповедник «Несвиж». Если поездка строится вокруг замка и парка, сравнивайте адрес квартиры с этой стороной города. Ратуша, Слуцкие ворота и Фарный костёл Божьего Тела находятся в историческом центре: от части домов до них идут пешком, от других удобнее ехать.</p>
<p>Поездом обычно приезжают на станцию Городея в одноимённом городском посёлке Несвижского района, около 14 км от Несвижа. Дальше ходят рейсовые автобусы, поэтому время заселения лучше согласовать с запасом на эту дорогу. Мирский замок расположен не здесь, а в городском посёлке Мир Кореличского района: если он в маршруте, заложите отдельную поездку. Свободные даты, стоимость и передачу ключей подтвердите у владельца.</p>
HTML,
                'faq' => [
                    [
                        'question' => 'Где искать квартиру, если цель поездки — Несвижский замок?',
                        'answer' => 'Сравнивайте адрес с северо-восточной частью города и прудами у дворца. Исторический центр с ратушей и костёлом лежит отдельно: маршрут от конкретного дома лучше проверить по карте.',
                    ],
                    [
                        'question' => 'Как выбрать жильё при приезде через станцию Городея?',
                        'answer' => 'Городея находится примерно в 14 км от Несвижа. Учтите дорогу на автобусе или такси и заранее согласуйте время получения ключей.',
                    ],
                    [
                        'question' => 'Находится ли Мирский замок в Несвиже?',
                        'answer' => 'Нет. Мирский замок стоит в городском посёлке Мир Кореличского района. Посещение требует отдельного маршрута, пешком из Несвижа туда не дойти.',
                    ],
                    [
                        'question' => 'Что согласовать с владельцем до приезда в Несвиж?',
                        'answer' => 'Подтвердите даты, итоговую стоимость, число гостей, точный адрес, время заселения и порядок передачи ключей.',
                    ],
                ],
            ],
            'glubokoe' => [
                'seo' => <<<'HTML'
<p>Глубокое стоит у нескольких озёр. Название связывают с озером, которое сейчас зовут Кагальным; его соединяет с Великим канал, и набережная Кагального часто становится точкой прогулок. В исторической части рядом оказываются костёл Святой Троицы на улице Советской и собор Рождества Пресвятой Богородицы на площади 17 Сентября. Для рабочей поездки эти ориентиры могут не подойти: тогда отталкивайтесь от адреса организации.</p>
<p>В городе есть железнодорожная станция Глубокое. Если едете поездом, сверьте путь от станции до дома и время заезда. В июле здесь проходит Вишнёвый фестиваль: на эти дни квартиры разбирают раньше, а в центре и у набережной бывает шумнее обычного. Смотрите точный адрес, фотографии и число спальных мест, затем подтвердите условия у владельца.</p>
HTML,
                'faq' => [
                    [
                        'question' => 'В какой части Глубокого искать квартиру для прогулок?',
                        'answer' => 'Для знакомства с городом удобны адреса с выходом к набережной Кагального озера и историческому центру, где стоят костёл Святой Троицы и собор Рождества Пресвятой Богородицы.',
                    ],
                    [
                        'question' => 'Как выбрать жильё при поездке в Глубокое на поезде?',
                        'answer' => 'Проверьте маршрут от станции Глубокое до адреса квартиры и заранее согласуйте время заселения с учётом прибытия.',
                    ],
                    [
                        'question' => 'Нужно ли учитывать Вишнёвый фестиваль при выборе квартиры в Глубоком?',
                        'answer' => 'Да, если вы приезжаете в июле. На даты фестиваля жильё лучше бронировать заранее, а если нужен тихий вечер, сравните адрес с центром и набережной.',
                    ],
                    [
                        'question' => 'Что уточнить у владельца до поездки в Глубокое?',
                        'answer' => 'Уточните свободные даты, стоимость, число гостей, точный адрес, правила проживания и порядок получения ключей.',
                    ],
                ],
            ],
        ];
    }
}
