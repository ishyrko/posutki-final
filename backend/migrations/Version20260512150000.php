<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Static page "Политика конфиденциальности": seed posutki.by copy, fill empty body, refresh legacy RNB.by default meta.
 */
final class Version20260512150000 extends AbstractMigration
{
    private const SLUG = 'politika-konfidentsialnosti';

    private const LEGACY_META_TITLE = 'Политика конфиденциальности — RNB.by';

    public function getDescription(): string
    {
        return 'Seed or fill static page politika-konfidentsialnosti (posutki.by privacy policy)';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'HTML'
<p>Настоящая политика описывает, какие данные обрабатывает posutki.by при использовании сервиса посуточной аренды жилья, в каких целях они используются и как мы обеспечиваем их защиту.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Какие данные мы собираем</h2>
<p>Мы можем обрабатывать данные, которые вы указываете при регистрации, размещении объявлений, бронировании и обращении в поддержку: контактные данные, сведения об объекте размещения, переписку по сделкам, а также технические данные (в том числе cookie и данные об устройстве и сессии), необходимые для работы сайта и безопасности.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Для чего мы используем данные</h2>
<p>Данные используются для предоставления функций сервиса, модерации объявлений, связи с пользователями, улучшения интерфейса и качества поддержки, а также для выполнения требований законодательства Республики Беларусь.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Использование cookie</h2>
<p>Файлы cookie помогают сохранять настройки (например, регион или валюту отображения), обеспечивать вход в аккаунт и корректную работу сайта. Продолжая пользоваться posutki.by, вы соглашаетесь с использованием cookie в соответствии с настройками вашего браузера и этой политикой.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Контакты</h2>
<p>По вопросам обработки персональных данных вы можете связаться с нами через раздел <a href="/kontakty/">«Контакты»</a>.</p>
</div>
</div>
HTML;

        $metaTitle = 'Политика конфиденциальности — posutki.by';
        $metaDescription = 'Условия обработки персональных данных и использования cookie на posutki.by.';

        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM static_pages WHERE slug = ?',
            [self::SLUG]
        );
        if ($count === 0) {
            $this->addSql(
                'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [self::SLUG, 'Политика конфиденциальности', $content, $metaTitle, $metaDescription]
            );
        }

        $this->addSql(
            'UPDATE static_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ? AND LENGTH(TRIM(content)) = 0',
            ['Политика конфиденциальности', $content, $metaTitle, $metaDescription, self::SLUG]
        );

        $this->addSql(
            'UPDATE static_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ? AND meta_title = ?',
            ['Политика конфиденциальности', $content, $metaTitle, $metaDescription, self::SLUG, self::LEGACY_META_TITLE]
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('politika-konfidentsialnosti seed/fill cannot be safely reverted.');
    }
}
