<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace EUR with RUB in exchange_rates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM exchange_rates WHERE currency = 'EUR'");

        $this->addSql("
            INSERT INTO exchange_rates (currency, rate_to_byn, updated_at)
            VALUES ('RUB', 0.0340, NOW())
            ON DUPLICATE KEY UPDATE currency = currency
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM exchange_rates WHERE currency = 'RUB'");

        $this->addSql("
            INSERT INTO exchange_rates (currency, rate_to_byn, updated_at)
            VALUES ('EUR', 3.4900, NOW())
        ");
    }
}
