<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Minsk landmarks: TSUM, GUM (inactive by default)';
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
               AND l.slug IN ('tsum', 'gum')"
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
                'name' => 'ЦУМ',
                'name_genitive' => 'ЦУМа',
                'slug' => 'tsum',
                'latitude' => 53.916141,
                'longitude' => 27.586172,
                'category' => 'mall',
                'short_description' => 'Центральный универмаг на проспекте Независимости у метро «Площадь Якуба Коласа»: магазины и шопинг в Советском районе.',
                'description' => '<p>ЦУМ Минск — крупный универмаг на пр. Независимости, 54, рядом со станцией метро «Площадь Якуба Коласа» и одноимённой площадью.</p><p>Удобен для гостей, которые хотят жить на главной магистрали города с магазинами, кафе и транспортом рядом.</p>',
                'address' => 'пр. Независимости, 54, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'универмаг'],
                    ['label' => 'Метро', 'value' => 'Площадь Якуба Коласа'],
                    ['label' => 'Район', 'value' => 'Советский'],
                ],
                'guest_tips' => [
                    'От метро «Площадь Якуба Коласа» — несколько минут пешком.',
                    'Удобно совместить с прогулкой по проспекту Независимости.',
                ],
                'sort_order' => 150,
            ],
            [
                'name' => 'ГУМ',
                'name_genitive' => 'ГУМа',
                'slug' => 'gum',
                'latitude' => 53.900553,
                'longitude' => 27.558106,
                'category' => 'mall',
                'short_description' => 'Главный универмаг Минска на проспекте Независимости: историческое здание, магазины и центр города у метро «Октябрьская».',
                'description' => '<p>ГУМ — первый крупный универмаг Минска, открытый в 1951 году. Здание на пр. Независимости, 21 — памятник архитектуры XX века в центре столицы.</p><p>Рядом площадь Независимости и станция метро «Октябрьская» — удобная точка для гостей, которые хотят жить в центре с шопингом и достопримечательностями рядом.</p>',
                'address' => 'пр. Независимости, 21, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'универмаг'],
                    ['label' => 'Открыт', 'value' => '1951'],
                    ['label' => 'Метро', 'value' => 'Октябрьская'],
                ],
                'guest_tips' => [
                    'От метро «Октябрьская» — несколько минут пешком по проспекту Независимости.',
                    'Удобно совместить с прогулкой по центру и площади Независимости.',
                ],
                'sort_order' => 160,
            ],
        ];
    }
}
