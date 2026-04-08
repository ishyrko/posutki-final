<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed article_categories and add FK from articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO article_categories (name, slug, sort_order) VALUES
            ('Новости рынка', 'rynok', 1),
            ('Гиды для покупателей', 'pokupatelyam', 2),
            ('Советы продавцам', 'prodavtsam', 3),
            ('Инвестиции', 'investitsii', 4),
            ('Юридические вопросы', 'pravo', 5),
            ('Обзоры районов', 'rayony', 6),
            ('Ремонт и дизайн', 'remont', 7)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM article_categories WHERE slug IN ('rynok','pokupatelyam','prodavtsam','investitsii','pravo','rayony','remont')");
    }
}
