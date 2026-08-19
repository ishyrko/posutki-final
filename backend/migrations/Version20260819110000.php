<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819110000 extends AbstractMigration
{
    /** @var list<string> */
    private const LISTING_SUGGESTED_SLUGS = [
        'minsk',
        'brest',
        'vitebsk',
        'gomel',
        'grodno',
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
        'borisov',
        'slonim',
        'lida',
        'mozyr',
        'polotsk',
        'rechitsa',
        'soligorsk',
        'ostrovets',
    ];

    public function getDescription(): string
    {
        return 'Add is_listing_suggested to cities, seed listing form suggestions, add slug and flag indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD is_listing_suggested TINYINT(1) NOT NULL DEFAULT 0');

        $slugs = self::LISTING_SUGGESTED_SLUGS;
        $placeholders = implode(', ', array_fill(0, count($slugs), '?'));
        $this->addSql(
            sprintf('UPDATE cities SET is_listing_suggested = 1 WHERE slug IN (%s)', $placeholders),
            $slugs,
        );

        $this->addSql('CREATE INDEX IDX_CITIES_IS_LISTING_SUGGESTED ON cities (is_listing_suggested)');
        $this->addSql('CREATE INDEX IDX_CITIES_SLUG ON cities (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_CITIES_SLUG ON cities');
        $this->addSql('DROP INDEX IDX_CITIES_IS_LISTING_SUGGESTED ON cities');
        $this->addSql('ALTER TABLE cities DROP is_listing_suggested');
    }
}
