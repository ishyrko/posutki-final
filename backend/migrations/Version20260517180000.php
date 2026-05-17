<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add messenger flags to user_phones and users (viber, whatsapp, telegram)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_phones ADD has_viber TINYINT(1) DEFAULT 0 NOT NULL, ADD has_whatsapp TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE users ADD phone_has_viber TINYINT(1) DEFAULT 0 NOT NULL, ADD phone_has_whatsapp TINYINT(1) DEFAULT 0 NOT NULL, ADD telegram VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_phones DROP has_viber, DROP has_whatsapp');
        $this->addSql('ALTER TABLE users DROP phone_has_viber, DROP phone_has_whatsapp, DROP telegram');
    }
}
