<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'User legal profiles for daily listings + properties.seller_type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_individual_profiles (
            user_id INT NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            middle_name VARCHAR(100) DEFAULT NULL,
            unp VARCHAR(9) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_individual_profiles ADD CONSTRAINT FK_8F8DA8F8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE user_business_profiles (
            user_id INT NOT NULL,
            organization_name VARCHAR(255) NOT NULL,
            contact_name VARCHAR(200) NOT NULL,
            unp VARCHAR(9) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_business_profiles ADD CONSTRAINT FK_7D6E0F7EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE properties ADD seller_type VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE properties DROP seller_type');
        $this->addSql('ALTER TABLE user_business_profiles DROP FOREIGN KEY FK_7D6E0F7EA76ED395');
        $this->addSql('DROP TABLE user_business_profiles');
        $this->addSql('ALTER TABLE user_individual_profiles DROP FOREIGN KEY FK_8F8DA8F8A76ED395');
        $this->addSql('DROP TABLE user_individual_profiles');
    }
}
