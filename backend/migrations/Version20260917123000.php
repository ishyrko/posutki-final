<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Район другого города исключает только спутники Минска и областных центров.
 * Фаниполь, Белоозёрск, Микашевичи — отдельные города со своими объявлениями.
 */
final class Version20260917123000 extends AbstractMigration
{
    /** @var list<string> */
    private const ENABLE_BOTH = [
        'fanipol-g',
        'beloozersk-g',
        'mikashevichi-g',
    ];

    public function getDescription(): string
    {
        return 'Enable catalog cities that sit in another rayon, except satellites of Minsk and oblast centers';
    }

    public function up(Schema $schema): void
    {
        $placeholders = implode(', ', array_fill(0, count(self::ENABLE_BOTH), '?'));
        $this->addSql(
            sprintf(
                'UPDATE cities SET is_listing_suggested = 1, is_apartment_catalog = 1 WHERE slug IN (%s)',
                $placeholders,
            ),
            self::ENABLE_BOTH,
        );
    }

    public function down(Schema $schema): void
    {
        $placeholders = implode(', ', array_fill(0, count(self::ENABLE_BOTH), '?'));
        $this->addSql(
            sprintf(
                'UPDATE cities SET is_listing_suggested = 0, is_apartment_catalog = 0 WHERE slug IN (%s)',
                $placeholders,
            ),
            self::ENABLE_BOTH,
        );
    }
}
