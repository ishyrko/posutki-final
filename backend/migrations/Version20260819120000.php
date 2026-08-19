<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819120000 extends AbstractMigration
{
    /** @var list<string> */
    private const APARTMENT_CATALOG_CITY_SLUGS = [
        'minsk',
        'brest',
        'vitebsk',
        'grodno',
        'gomel',
        'mogilev',
        'baranovichi',
        'bobruysk',
        'volkovysk',
        'glubokoe',
        'zhlobin',
        'zhodino',
        'krichev',
        'logoysk',
        'molodechno',
        'nesvizh',
        'novolukoml',
        'novopolotsk',
        'orsha',
        'pinsk',
        'svetlogorsk',
        'smorgon',
    ];

    public function getDescription(): string
    {
        return 'Add is_apartment_catalog to cities and seed catalog homepage cities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD is_apartment_catalog TINYINT(1) NOT NULL DEFAULT 0');

        foreach (self::APARTMENT_CATALOG_CITY_SLUGS as $slug) {
            $this->addSql(
                'UPDATE cities SET is_apartment_catalog = 1 WHERE slug = ?',
                [$slug],
            );
        }

        $this->addSql('CREATE INDEX IDX_CITIES_IS_APARTMENT_CATALOG ON cities (is_apartment_catalog)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_CITIES_IS_APARTMENT_CATALOG ON cities');
        $this->addSql('ALTER TABLE cities DROP is_apartment_catalog');
    }
}
