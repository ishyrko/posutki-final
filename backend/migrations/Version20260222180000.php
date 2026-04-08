<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create exchange_rates table and add price_byn column to properties';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE exchange_rates (
                currency VARCHAR(3) NOT NULL,
                rate_to_byn DECIMAL(10, 4) NOT NULL,
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(currency)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('ALTER TABLE properties ADD price_byn INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_properties_price_byn ON properties (price_byn)');

        $this->addSql("
            UPDATE properties SET price_byn = CAST(JSON_EXTRACT(price, '$.amount') AS UNSIGNED)
            WHERE JSON_UNQUOTE(JSON_EXTRACT(price, '$.currency')) = 'BYN'
        ");

        $this->addSql("
            INSERT INTO exchange_rates (currency, rate_to_byn, updated_at)
            VALUES ('USD', 3.2700, NOW()), ('EUR', 3.4900, NOW())
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_properties_price_byn ON properties');
        $this->addSql('ALTER TABLE properties DROP COLUMN price_byn');
        $this->addSql('DROP TABLE exchange_rates');
    }
}
