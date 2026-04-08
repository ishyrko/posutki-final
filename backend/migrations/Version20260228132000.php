<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Service\SlugGenerator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228132000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Green line metro stations with coordinates';
    }

    public function up(Schema $schema): void
    {
        $stations = [
            [31, 1, 3, 301, 'Ковальская Слобода', 53.877678, 27.549564],
            [32, 1, 3, 302, 'Вокзальная', 53.889322, 27.547721],
            [33, 1, 3, 303, 'Пл. Франтишка Богушевича', 53.896445, 27.537979],
            [34, 1, 3, 304, 'Юбилейная площадь', 53.904765, 27.540499],
            [35, 1, 3, 305, 'Славинская', 53.901500, 27.545000],
            [36, 1, 3, 306, 'Аэродромная', 53.894000, 27.543000],
            [37, 1, 3, 307, 'Машинозаводская', 53.887000, 27.541000],
            [38, 1, 3, 308, 'Новаторская', 53.880000, 27.539000],
        ];

        foreach ($stations as [$id, $cityId, $line, $order, $name, $lat, $lon]) {
            $this->addSql(
                'INSERT INTO metro_stations (id, city_id, line, sort_order, name, slug, latitude, longitude)
                 VALUES (:id, :cityId, :line, :sortOrder, :name, :slug, :lat, :lon)',
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
        $this->addSql('DELETE FROM metro_stations WHERE id IN (31, 32, 33, 34, 35, 36, 37, 38)');
    }
}
