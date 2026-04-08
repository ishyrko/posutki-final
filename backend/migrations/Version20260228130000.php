<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Service\SlugGenerator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create metro_stations table and seed Minsk stations from metro.sql with generated slugs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE metro_stations (
                id INT NOT NULL,
                city_id INT NOT NULL,
                line SMALLINT NOT NULL,
                sort_order SMALLINT NOT NULL,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                latitude DOUBLE PRECISION DEFAULT NULL,
                longitude DOUBLE PRECISION DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX idx_metro_city (city_id),
                INDEX idx_metro_line_order (line, sort_order),
                UNIQUE INDEX uniq_metro_city_slug (city_id, slug)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`'
        );

        $stations = [
            [1, 1, 1, 6, 'Площадь Ленина', 53.892953, 27.547778],
            [2, 1, 1, 9, 'Площадь Якуба Коласа', 53.915836, 27.584031],
            [3, 1, 2, 114, 'Каменная горка', 53.906857, 27.437605],
            [4, 1, 1, 2, 'Петровщина', 53.864737, 27.48616],
            [5, 1, 2, 101, 'Могилёвская', 53.861985, 27.674157],
            [6, 1, 1, 5, 'Институт культуры', 53.886075, 27.540356],
            [7, 1, 1, 15, 'Уручье', 53.945385, 27.68626],
            [8, 1, 1, 10, 'Академия наук', 53.921898, 27.599063],
            [9, 1, 2, 111, 'Пушкинская', 53.909651, 27.49713],
            [10, 1, 2, 108, 'Немига', 53.905749, 27.554018],
            [11, 1, 1, 7, 'Октябрьская', 53.905749, 27.554018],
            [12, 1, 2, 103, 'Партизанская', 53.876122, 27.628971],
            [13, 1, 1, 8, 'Площадь Победы', 53.909473, 27.576201],
            [14, 1, 1, 12, 'Московская', 53.927893, 27.627543],
            [15, 1, 2, 113, 'Кунцевщина', 53.90626, 27.453983],
            [16, 1, 2, 104, 'Тракторный завод', 53.890045, 27.614768],
            [17, 1, 2, 109, 'Фрунзенская', 53.905344, 27.539336],
            [19, 1, 2, 102, 'Автозаводская', 53.868958, 27.648871],
            [20, 1, 1, 13, 'Восток', 53.934478, 27.651208],
            [21, 1, 2, 112, 'Спортивная', 53.908469, 27.479726],
            [22, 1, 2, 105, 'Пролетарская', 53.89026, 27.586489],
            [23, 1, 1, 11, 'Парк Челюскинцев', 53.924165, 27.613378],
            [24, 1, 2, 110, 'Молодёжная', 53.906626, 27.522498],
            [25, 1, 1, 14, 'Борисовский тракт', 53.938811, 27.666049],
            [26, 1, 1, 3, 'Михалово', 53.87674, 27.496922],
            [27, 1, 1, 4, 'Грушевка', 53.88658, 27.514772],
            [28, 1, 2, 106, 'Первомайская', 53.893884, 27.570787],
            [29, 1, 2, 107, 'Купаловская', 53.900857, 27.561759],
            [30, 1, 1, 1, 'Малиновка', 53.849962, 27.4748],
        ];

        foreach ($stations as [$id, $cityId, $line, $order, $name, $lat, $lon]) {
            $this->addSql(
                'INSERT INTO metro_stations (id, city_id, line, sort_order, name, slug, latitude, longitude) VALUES (:id, :cityId, :line, :sortOrder, :name, :slug, :lat, :lon)',
                [
                    'id' => $id,
                    'cityId' => $cityId,
                    'line' => $line,
                    'sortOrder' => $order,
                    'name' => $name,
                    'slug' => SlugGenerator::slugify($name),
                    'lat' => $lat,
                    'lon' => $lon,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE metro_stations');
    }
}
