<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Терминология типа жилья на странице «О нас»: дома → усадьбы.
 */
final class Version20260925183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update o-nas static page copy: houses → estates (усадьбы)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            UPDATE static_pages
            SET
                content = REPLACE(
                    REPLACE(content, 'квартир, домов', 'квартир, усадеб'),
                    'квартир и домов',
                    'квартир и усадеб'
                ),
                meta_description = REPLACE(
                    REPLACE(meta_description, 'квартир, домов', 'квартир, усадеб'),
                    'квартир и домов',
                    'квартир и усадеб'
                ),
                updated_at = NOW()
            WHERE slug = 'o-nas'
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            UPDATE static_pages
            SET
                content = REPLACE(
                    REPLACE(content, 'квартир, усадеб', 'квартир, домов'),
                    'квартир и усадеб',
                    'квартир и домов'
                ),
                meta_description = REPLACE(
                    REPLACE(meta_description, 'квартир, усадеб', 'квартир, домов'),
                    'квартир и усадеб',
                    'квартир и домов'
                ),
                updated_at = NOW()
            WHERE slug = 'o-nas'
            SQL
        );
    }
}
