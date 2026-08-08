<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Brest landmark: Church of St. Nicholas the Wonderworker (Bratskaya) on Sovetskaya (inactive by default)';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot seed landmarks.');

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
             WHERE c.slug = 'brest'
               AND l.slug IN ('tserkov-svyatogo-nikolaya-chudotvortsa')"
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
                'name' => 'Церковь Святого Николая Чудотворца',
                'name_genitive' => 'церкви Святого Николая Чудотворца',
                'slug' => 'tserkov-svyatogo-nikolaya-chudotvortsa',
                'latitude' => 52.098913,
                'longitude' => 23.689759,
                'category' => 'sight',
                'short_description' => 'Свято-Николаевская братская церковь на пешеходной ул. Советской — памятник архитектуры начала XX века в центре Бреста.',
                'description' => '<p>Церковь Святого Николая Чудотворца (Свято-Николаевская братская церковь) построена в 1904—1906 годах на пожертвования горожан и моряков. Храм стоит в сердце Бреста на ул. Советской и входит в список историко-культурных ценностей Беларуси.</p><p>Не путать с гарнизонным Свято-Николаевским собором в Брестской крепости — это отдельный городской храм на главной пешеходной улице.</p>',
                'address' => 'ул. Советская, 10, Брест',
                'facts' => [
                    ['label' => 'Построена', 'value' => '1904—1906'],
                    ['label' => 'Тип', 'value' => 'братская церковь'],
                    ['label' => 'Стиль', 'value' => 'русский'],
                ],
                'guest_tips' => [
                    'Удобно совместить с прогулкой по пешеходной ул. Советской.',
                    'Это не собор в крепости — храм находится в центре города.',
                ],
                'sort_order' => 70,
            ],
        ];
    }
}
