<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Catalog SEO for Lida (city page and room landings 1–3).
 *
 * Geographic claims: belarus.by (castle 1323, Lideya/Kamenka, 112 km from Grodno,
 * station Lida, trains from Grodno and Molodechno, Zamkavy Hascinets May–August),
 * lixmuseum.by (castle museum), ru.belarus.travel (Exaltation of the Holy Cross church),
 * museum.lidskae.by (Lidskae brewery museum, Mitskevicha 32, tickets in advance).
 */
final class Version20260822120000 extends AbstractMigration
{
    private const CITY_SLUG = 'lida';

    public function getDescription(): string
    {
        return 'Fill catalog SEO text and FAQ for Lida, including room landings';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne(
            'SELECT id FROM cities WHERE slug = ?',
            [self::CITY_SLUG],
        );
        if ($cityId === false) {
            throw new \RuntimeException('Cannot fill catalog SEO: missing city lida.');
        }

        $normalizer = $this->catalogContentNormalizer();
        $cityContent = $this->cityContent();
        $seo = $normalizer->normalizeSeoText($cityContent['seo']);
        $faqJson = json_encode(
            $normalizer->normalizeFaq($cityContent['faq']),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $this->addSql(
            'UPDATE cities
             SET catalog_seo_text = ?, catalog_faq = ?, catalog_seo_visible = 1, is_apartment_catalog = 1
             WHERE slug = ?',
            [$seo, $faqJson, self::CITY_SLUG],
        );

        foreach ($this->roomContent() as $bucket => $entry) {
            $roomSeo = $normalizer->normalizeSeoText($entry['seo']);
            $roomFaqJson = json_encode(
                $normalizer->normalizeFaq($entry['faq']),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            $rowExists = $this->connection->fetchOne(
                'SELECT 1 FROM city_room_catalog_contents
                 WHERE city_id = ? AND rooms_bucket = ?',
                [(int) $cityId, $bucket],
            );

            if ($rowExists !== false) {
                $this->addSql(
                    'UPDATE city_room_catalog_contents
                     SET catalog_seo_text = ?, catalog_faq = ?, catalog_seo_visible = 1
                     WHERE city_id = ? AND rooms_bucket = ?',
                    [$roomSeo, $roomFaqJson, (int) $cityId, $bucket],
                );
            } else {
                $this->addSql(
                    'INSERT INTO city_room_catalog_contents
                        (city_id, rooms_bucket, catalog_seo_text, catalog_faq, catalog_seo_visible, created_at)
                     VALUES (?, ?, ?, ?, 1, NOW())',
                    [(int) $cityId, $bucket, $roomSeo, $roomFaqJson],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Lida catalog SEO text, FAQ and visibility cannot be restored safely.',
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
<p>Лидский замок стоит у слияния рек Лидеи и Каменки, на улице Замковой. Строительство начали в 1323 году при великом князе Гедимине; сейчас это музейный комплекс с экспозициями в башнях. Если поездка строится вокруг замка, сверяйте адрес квартиры с этой частью города. Крестовоздвиженский костёл на Советской и Свято-Михайловский собор тоже в центре, но от станции Лида до них уже отдельный путь: вокзал и двор не одно и то же.</p>
<p>Поездом приезжают на станцию Лида, в том числе из Гродно и Молодечно. Время ключей лучше согласовать с запасом на дорогу от перрона. С мая по август по выходным у стен замка идёт «Замкавы гасцінец», летом здесь же бывают фестивали и День города: на эти даты квартиры разбирают раньше, вечером в центре шумнее обычного. Музей лидского бровара на Мицкевича, 32, пускает только по заранее купленным билетам, поэтому жить «у завода» имеет смысл, если экскурсия реально в плане. Свободные даты, стоимость и передачу ключей подтвердите у владельца.</p>
HTML,
            'faq' => [
                [
                    'question' => 'Где искать квартиру, если цель поездки — Лидский замок?',
                    'answer' => 'Сравнивайте адрес с улицей Замковой и центром у слияния Лидеи и Каменки. Станция Лида лежит отдельно: маршрут от конкретного дома лучше проверить по карте.',
                ],
                [
                    'question' => 'Как выбрать жильё при приезде на станцию Лида?',
                    'answer' => 'Сверьте путь от вокзала до квартиры и заранее согласуйте время заезда. Рейсы есть, в частности, из Гродно и Молодечно.',
                ],
                [
                    'question' => 'Нужно ли учитывать летние события у замка в Лиде?',
                    'answer' => 'Да, если вы приезжаете с мая по август. На выходные «Замкавы гасцінец» и фестивальные дни жильё лучше бронировать раньше, а если нужен тихий вечер — сравните адрес с замковым двором.',
                ],
                [
                    'question' => 'Что согласовать с владельцем до приезда в Лиду?',
                    'answer' => 'Подтвердите даты, итоговую стоимость, число гостей, точный адрес, время заселения и порядок передачи ключей.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{seo: string, faq: list<array{question: string, answer: string}>}>
     */
    private function roomContent(): array
    {
        return [
            1 => [
                'seo' => <<<'HTML'
<p>Для ночёвки в Лиде после замка однокомнатной квартиры обычно достаточно. Смотрите, сколько идти от дома до Замковой: после осмотра башен тащиться через весь город уже не хочется. Если поезд поздний, важнее короткий путь от станции Лида, чем вид на стены.</p>
<p>Студия и квартира с отдельной кухней в каталоге стоят рядом, устройство сна у них разное. Билеты в Музей лидского бровара покупают заранее, к заселению это не привязать. Когда одной комнаты мало, откройте <a href="/lida/kvartiry/">все квартиры Лиды</a>.</p>
HTML,
                'faq' => [
                    ['question' => 'Где остановиться, если весь день у Лидского замка?', 'answer' => 'Ближе к улице Замковой, чтобы вечером не пересекать город пешком.'],
                    ['question' => 'Что важнее при позднем поезде в Лиду?', 'answer' => 'Дорога от станции Лида и заранее согласованное время ключей.'],
                    ['question' => 'Хватит ли студии паре в Лиде?', 'answer' => 'Да, если обоих устраивает общее пространство кухни и спальни.'],
                ],
            ],
            2 => [
                'seo' => <<<'HTML'
<p>Двухкомнатная квартира в Лиде нужна, когда ребёнку или второму взрослому нужна своя комната. С мая по август у замка по выходным проходит «Замкавы гасцінец»: если дети ложатся рано, дом прямо у двора может оказаться шумнее, чем улица в стороне от Замковой.</p>
<p>Проверьте, не проходная ли гостиная и на чём спит третий человек. Костёл на Советской, вокзал и пивзавод на Мицкевича — три разных точки, одна квартира редко удобна всем сразу. Если планировка важнее адреса, смотрите также <a href="/lida/kvartiry/">общий каталог города</a>.</p>
HTML,
                'faq' => [
                    ['question' => 'Подойдёт ли две комнаты семье на выходные в Лиде?', 'answer' => 'Да, если ребёнку хватает отдельной комнаты и вас устраивает вечерний шум у выбранного дома.'],
                    ['question' => 'Жить только у замка в Лиде?', 'answer' => 'Имеет смысл для пешей программы. Для позднего поезда удобнее адреса ближе к станции Лида.'],
                    ['question' => 'Где спит третий гость в двухкомнатной квартире в Лиде?', 'answer' => 'Чаще на диване в гостиной. Тип места смотрите в объявлении.'],
                ],
            ],
            3 => [
                'seo' => <<<'HTML'
<p>Три комнаты в Лиде берут, когда компания не хочет делить одну спальню: часть едет на экскурсию в бровар, часть остаётся у замка. Гостиную имеет смысл оставить общей, а не превращать в четвёртое спальное место. Гродно отсюда около 112 км, это уже отдельный день, а не прогулка.</p>
<p>Считайте кровати, а не комнаты. Группе с чемоданами после станции Лида важнее понятный заезд, чем формальная близость к стенам. Если большая квартира не нужна, в <a href="/lida/kvartiry/">каталоге Лиды</a> есть более компактные варианты.</p>
HTML,
                'faq' => [
                    ['question' => 'Удобна ли большая квартира группе, которая едет ещё и в Гродно?', 'answer' => 'Да, как общая база. До Гродно около 112 км, пешком от Лиды туда не дойти.'],
                    ['question' => 'Как понять, сколько человек влезет в квартиру в Лиде?', 'answer' => 'По кроватям и диванам в объявлении, а не по числу комнат.'],
                    ['question' => 'Что учесть компании с поездом в Лиду?', 'answer' => 'Время заезда с запасом и адрес, до которого реально добраться от станции Лида с багажом.'],
                ],
            ],
        ];
    }
}
