<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Belarus cities data';
    }

    public function up(Schema $schema): void
    {
        // Major cities of Belarus with regions
        $cities = [
            // Minsk Region
            ['Minsk', 'minsk', 'Minsk', 1],
            ['Borisov', 'borisov', 'Minsk', 1],
            ['Soligorsk', 'soligorsk', 'Minsk', 1],
            ['Molodechno', 'molodechno', 'Minsk', 1],
            ['Zhodino', 'zhodino', 'Minsk', 1],
            ['Slutsk', 'slutsk', 'Minsk', 1],

            // Brest Region
            ['Brest', 'brest', 'Brest', 1],
            ['Baranovichi', 'baranovichi', 'Brest', 1],
            ['Pinsk', 'pinsk', 'Brest', 1],
            ['Kobrin', 'kobrin', 'Brest', 1],

            // Gomel Region
            ['Gomel', 'gomel', 'Gomel', 1],
            ['Mozyr', 'mozyr', 'Gomel', 1],
            ['Zhlobin', 'zhlobin', 'Gomel', 1],
            ['Svetlogorsk', 'svetlogorsk', 'Gomel', 1],
            ['Rechitsa', 'rechitsa', 'Gomel', 1],

            // Grodno Region
            ['Grodno', 'grodno', 'Grodno', 1],
            ['Lida', 'lida', 'Grodno', 1],
            ['Slonim', 'slonim', 'Grodno', 1],
            ['Volkovysk', 'volkovysk', 'Grodno', 1],

            // Mogilev Region
            ['Mogilev', 'mogilev', 'Mogilev', 1],
            ['Bobruisk', 'bobruisk', 'Mogilev', 1],
            ['Orsha', 'orsha', 'Mogilev', 1],
            ['Gorki', 'gorki', 'Mogilev', 1],

            // Vitebsk Region
            ['Vitebsk', 'vitebsk', 'Vitebsk', 1],
            ['Orsha', 'orsha-vitebsk', 'Vitebsk', 1],
            ['Novopolotsk', 'novopolotsk', 'Vitebsk', 1],
            ['Polotsk', 'polotsk', 'Vitebsk', 1],
        ];

        foreach ($cities as $city) {
            $this->addSql(
                'INSERT INTO cities (name, slug, region, is_active) VALUES (?, ?, ?, ?)',
                $city
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM cities');
    }
}