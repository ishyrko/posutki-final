<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260329130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create static_pages table and seed privacy policy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE static_pages (
            id INT AUTO_INCREMENT NOT NULL COMMENT \'(DC2Type:id)\',
            slug VARCHAR(255) NOT NULL COMMENT \'(DC2Type:slug)\',
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            meta_title VARCHAR(255) DEFAULT NULL,
            meta_description LONGTEXT DEFAULT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_static_pages_slug (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $content = <<<'HTML'
<p>Настоящая политика описывает, какие данные собирает RNB.by, как они используются и каким образом мы обеспечиваем их защиту.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Какие данные мы собираем</h2>
<p>Мы можем обрабатывать данные, которые вы передаете при использовании сайта: контактные данные, информацию по объявлениям и технические данные, включая cookie.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Для чего мы используем данные</h2>
<p>Данные используются для работы сервиса, улучшения пользовательского опыта, связи с пользователями и выполнения требований законодательства.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Использование cookie</h2>
<p>Cookie помогают сохранять настройки, обеспечивать корректную работу сайта и анализировать использование сервиса. Продолжая использовать сайт, вы соглашаетесь с использованием cookie.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Контакты</h2>
<p>По вопросам обработки данных свяжитесь с нами по адресу: info@rnb.by.</p>
</div>
</div>
HTML;

        $this->addSql(
            'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [
                'politika-konfidentsialnosti',
                'Политика конфиденциальности',
                $content,
                'Политика конфиденциальности — RNB.by',
                'Условия обработки персональных данных и использования cookie на RNB.by.',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE static_pages');
    }
}
