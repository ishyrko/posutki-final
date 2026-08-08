<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Brest landmark: forged lanterns alley on Gogol Street (inactive by default)';
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
               AND l.slug IN ('fonari-na-ulitse-gogolya')"
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
                'name' => 'Фонари на улице Гоголя',
                'name_genitive' => 'фонарей на улице Гоголя',
                'slug' => 'fonari-na-ulitse-gogolya',
                'latitude' => 52.091383,
                'longitude' => 23.685683,
                'category' => 'sight',
                'short_description' => 'Аллея кованых фонарей на ул. Гоголя — десятки уникальных арт-объектов в центре Бреста, часть из них посвящена произведениям Гоголя.',
                'description' => '<p>Аллея кованых фонарей на улице Гоголя открыта 27 июля 2013 года. Каждый фонарь — отдельная кованая скульптура со своей идеей: здесь есть пожарник, чистильщик обуви, «Нос», «Вечера на хуторе близ Диканьки» и другие композиции.</p><p>Аллея тянется по центру города рядом с пешеходной ул. Советской — удобная вечерняя прогулка, когда фонари зажигаются.</p>',
                'address' => 'ул. Гоголя, Брест',
                'facts' => [
                    ['label' => 'Открыта', 'value' => '2013'],
                    ['label' => 'Тип', 'value' => 'аллея кованых фонарей'],
                    ['label' => 'Фонарей', 'value' => 'около 40'],
                ],
                'guest_tips' => [
                    'Особенно красиво вечером, когда фонари включены.',
                    'Рядом пешеходная ул. Советская — удобно совместить прогулки.',
                ],
                'sort_order' => 80,
            ],
        ];
    }
}
