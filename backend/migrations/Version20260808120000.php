<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Minsk landmarks: TC Aviamoll, TC Galileo (inactive by default)';
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
               AND l.slug IN ('tc-aviamoll', 'tc-galileo')"
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
                'name' => 'ТЦ Авиамолл',
                'name_genitive' => 'ТЦ Авиамолл',
                'slug' => 'tc-aviamoll',
                'latitude' => 53.8671,
                'longitude' => 27.5486,
                'category' => 'mall',
                'short_description' => 'Крупный торгово-общественный центр Avia Mall у метро «Аэродромная»: магазины, фудкорт и гипермаркет Green в районе Минск-Мир.',
                'description' => '<p>ТЦ Авиамолл (Avia Mall) — торгово-общественный центр на ул. Братской, 18, рядом со станцией метро «Аэродромная» и жилым комплексом Минск-Мир.</p><p>Здесь магазины, большой фудкорт, гипермаркет Green и удобный паркинг — удобная точка для гостей, которые хотят жить рядом с шопингом и новой зелёной линией метро.</p>',
                'address' => 'ул. Братская, 18, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'торгово-общественный центр'],
                    ['label' => 'Метро', 'value' => 'Аэродромная'],
                    ['label' => 'Рядом', 'value' => 'Минск-Мир'],
                ],
                'guest_tips' => [
                    'От метро «Аэродромная» — около 2 минут пешком.',
                    'Рядом также станция «Ковальская Слобода» — удобно добираться из центра.',
                ],
                'sort_order' => 130,
            ],
            [
                'name' => 'ТЦ Галилео',
                'name_genitive' => 'ТЦ Галилео',
                'slug' => 'tc-galileo',
                'latitude' => 53.8906,
                'longitude' => 27.5502,
                'category' => 'mall',
                'short_description' => 'ТРЦ Galileo у вокзала и метро «Площадь Ленина»: шопинг, кинотеатр, фудкорт и паркинг в центре Минска.',
                'description' => '<p>ТЦ Галилео (ТРЦ Galileo) расположен на ул. Бобруйской, 6 — в шаге от железнодорожного вокзала Минск-Пассажирский, автовокзала «Центральный» и станции метро «Площадь Ленина».</p><p>Удобен для гостей, которые приезжают поездом или хотят жить в центре с крупным торговым комплексом, кафе и развлечениями рядом.</p>',
                'address' => 'ул. Бобруйская, 6, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'торгово-развлекательный центр'],
                    ['label' => 'Метро', 'value' => 'Площадь Ленина'],
                    ['label' => 'Рядом', 'value' => 'вокзал Минск-Пассажирский'],
                ],
                'guest_tips' => [
                    'От метро «Площадь Ленина» — несколько минут пешком.',
                    'Удобно совместить с приездом или отъездом с вокзала.',
                ],
                'sort_order' => 140,
            ],
        ];
    }
}
