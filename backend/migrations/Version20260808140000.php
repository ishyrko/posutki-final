<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Brest landmark: Museum of Railway Technology (inactive by default)';
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
               AND l.slug IN ('muzey-zheleznodorozhnoy-tehniki')"
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
                'name' => 'Музей железнодорожной техники',
                'name_genitive' => 'музея железнодорожной техники',
                'slug' => 'muzey-zheleznodorozhnoy-tehniki',
                'latitude' => 52.085656,
                'longitude' => 23.672185,
                'category' => 'sight',
                'short_description' => 'Открытая экспозиция паровозов, вагонов и железнодорожной техники у Брестской крепости — один из главных технических музеев Беларуси.',
                'description' => '<p>Брестский музей железнодорожной техники основан в 2002 году. На открытой площадке собраны десятки локомотивов, вагонов и служебных машин разных эпох — многие экспонаты действующие.</p><p>Музей расположен на проспекте Машерова, 2, рядом со входом в Брестскую крепость — удобная точка для гостей, которые хотят совместить исторический и технический маршруты.</p>',
                'address' => 'пр. Машерова, 2, Брест',
                'facts' => [
                    ['label' => 'Основан', 'value' => '2002'],
                    ['label' => 'Тип', 'value' => 'музей под открытым небом'],
                    ['label' => 'Рядом', 'value' => 'Брестская крепость'],
                ],
                'guest_tips' => [
                    'Удобно посетить в один день с Брестской крепостью — музей у главного входа.',
                    'Выходной день обычно понедельник — уточняйте режим перед визитом.',
                ],
                'sort_order' => 60,
            ],
        ];
    }
}
