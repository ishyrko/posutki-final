<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Minsk landmarks: Park Chelyuskintsev, Dana Mall, TC Galereya';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'minsk'");
        $this->abortIf($cityId === false, 'City "minsk" not found — cannot seed landmarks.');

        $cityId = (int) $cityId;

        foreach ($this->landmarks() as $landmark) {
            $this->addSql(
                'INSERT INTO landmarks (
                    city_id, name, name_genitive, slug, latitude, longitude, category,
                    short_description, description, address, facts, guest_tips, is_active, sort_order
                )
                SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM landmarks WHERE city_id = ? AND slug = ?
                )',
                [
                    $cityId,
                    $landmark['name'],
                    $landmark['name_genitive'],
                    $landmark['slug'],
                    $landmark['latitude'],
                    $landmark['longitude'],
                    $landmark['category'],
                    $landmark['short_description'],
                    $landmark['description'],
                    $landmark['address'],
                    json_encode($landmark['facts'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    json_encode($landmark['guest_tips'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    $landmark['sort_order'],
                    $cityId,
                    $landmark['slug'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE l FROM landmarks l
             INNER JOIN cities c ON c.id = l.city_id
             WHERE c.slug = 'minsk'
               AND l.slug IN ('park-chelyuskintsev', 'dana-mall', 'tc-galereya')"
        );
    }

    /**
     * @return list<array{
     *   name: string,
     *   name_genitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   short_description: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guest_tips: list<string>,
     *   sort_order: int
     * }>
     */
    private function landmarks(): array
    {
        return [
            [
                'name' => 'Парк Челюскинцев',
                'name_genitive' => 'Парка Челюскинцев',
                'slug' => 'park-chelyuskintsev',
                'latitude' => 53.9219,
                'longitude' => 27.6168,
                'category' => 'park',
                'short_description' => 'Один из центральных парков Минска у метро «Парк Челюскинцев»: аллеи, аттракционы и зелёная зона на проспекте Независимости.',
                'description' => '<p>Парк Челюскинцев — крупный парк культуры и отдыха в Первомайском районе Минска. Здесь удобно гулять, отдыхать с детьми и совмещать прогулку с поездкой на метро.</p><p>Рядом проспект Независимости и станция метро «Парк Челюскинцев» — удобная точка для краткосрочной аренды жилья в зелёном районе.</p>',
                'address' => 'пр. Независимости, 84/1, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'парк культуры и отдыха'],
                    ['label' => 'Метро', 'value' => 'Парк Челюскинцев'],
                    ['label' => 'Район', 'value' => 'Первомайский'],
                ],
                'guest_tips' => [
                    'От метро «Парк Челюскинцев» — несколько минут пешком.',
                    'Удобно совместить с прогулкой по проспекту Независимости.',
                ],
                'sort_order' => 100,
            ],
            [
                'name' => 'Dana Mall',
                'name_genitive' => 'Dana Mall',
                'slug' => 'dana-mall',
                'latitude' => 53.9337,
                'longitude' => 27.6525,
                'category' => 'mall',
                'short_description' => 'Крупный торгово-развлекательный центр у Национальной библиотеки и метро «Восток»: магазины, кинотеатр, фудкорт и паркинг.',
                'description' => '<p>Dana Mall — один из популярных ТРЦ Минска рядом с Национальной библиотекой. Здесь магазины международных и белорусских брендов, кинотеатр, детская зона и большой фудкорт.</p><p>До входа — около минуты пешком от метро «Восток», поэтому район удобен для гостей, которые хотят жить рядом с шопингом и транспортом.</p>',
                'address' => 'ул. Петра Мстиславца, 11, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'торгово-развлекательный центр'],
                    ['label' => 'Метро', 'value' => 'Восток'],
                    ['label' => 'Рядом', 'value' => 'Национальная библиотека'],
                ],
                'guest_tips' => [
                    'От метро «Восток» выходите в сторону Национальной библиотеки.',
                    'Есть подземный паркинг — удобно приезжать на машине.',
                ],
                'sort_order' => 110,
            ],
            [
                'name' => 'ТЦ Галерея',
                'name_genitive' => 'ТЦ Галерея',
                'slug' => 'tc-galereya',
                'latitude' => 53.9086,
                'longitude' => 27.5486,
                'category' => 'mall',
                'short_description' => 'Galleria Minsk на проспекте Победителей — шопинг и развлечения в центре, рядом с Немигой и Дворцом спорта.',
                'description' => '<p>ТРЦ Galleria Minsk (ТЦ Галерея) расположен на проспекте Победителей, 9 — в шаге от исторического центра, набережной Свислочи и Дворца спорта.</p><p>Удобен для гостей, которые хотят жить в центре и иметь рядом крупный торговый комплекс, кафе и транспорт.</p>',
                'address' => 'пр. Победителей, 9, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'торгово-развлекательный центр'],
                    ['label' => 'Метро', 'value' => 'Немига'],
                    ['label' => 'Рядом', 'value' => 'Дворец спорта'],
                ],
                'guest_tips' => [
                    'Ближайшее метро — «Немига», дальше несколько минут пешком.',
                    'Удобно совместить с прогулкой по центру и набережной.',
                ],
                'sort_order' => 120,
            ],
        ];
    }
}
