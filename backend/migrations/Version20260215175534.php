<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215175534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE articles (id CHAR(36) NOT NULL, author_id CHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, excerpt LONGTEXT NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, category_id INT DEFAULT NULL, tags JSON NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, views INT NOT NULL, UNIQUE INDEX UNIQ_BFDD3168989D9B62 (slug), INDEX IDX_BFDD31687B00651CE0D4FDE1 (status, published_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE cities (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, region VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, UNIQUE INDEX UNIQ_D95DB16B989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE properties (id CHAR(36) NOT NULL, owner_id CHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, deal_type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price JSON NOT NULL, area DOUBLE PRECISION NOT NULL, rooms INT NOT NULL, floor INT NOT NULL, total_floors INT NOT NULL, address JSON NOT NULL, city_id INT NOT NULL, coordinates JSON NOT NULL, images JSON NOT NULL, amenities JSON NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, published_at DATETIME DEFAULT NULL, views INT NOT NULL, INDEX IDX_87C331C78BAC62AF3AA332868CDE57297B00651C (city_id, deal_type, type, status), INDEX IDX_87C331C78B8E8428 (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_verified TINYINT NOT NULL, roles JSON NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE cities');
        $this->addSql('DROP TABLE properties');
        $this->addSql('DROP TABLE users');
    }
}
